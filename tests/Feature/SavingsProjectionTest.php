<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use App\Services\AssistantFinancialContext;
use App\Services\AssistantResponseGuard;
use App\Services\SavingsProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SavingsProjectionTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->today = Carbon::parse('2026-10-28 12:00:00');
        $this->travelTo($this->today);
    }

    private function project(float $saved, float $amount, string $frequency, ?string $deadline = null, int $horizon = 12, float $target = 10000): array
    {
        return app(SavingsProjectionService::class)->project(
            $target, $saved, $amount, $frequency, $this->today, $horizon,
            $deadline === null ? null : Carbon::parse($deadline)
        );
    }

    private function context(User $user, string $question): array
    {
        return app(AssistantFinancialContext::class)->forQuestion($user, $question, [], now()->startOfMonth(), now());
    }

    public function test_case_a_weekly_savings_without_a_deadline(): void
    {
        $plan = $this->project(200, 100, 'week');

        $this->assertSame('9800.00', $plan['remaining']);
        $this->assertSame(98, $plan['contributions_needed']);
        $this->assertSame('22.6', $plan['months_needed']);
        $this->assertSame('5400.00', $plan['projected_saved']);
        $this->assertSame('4600.00', $plan['shortfall']);
        $this->assertSame('2028-09-13', $plan['completion_date']);
        $this->assertArrayNotHasKey('deadline', $plan);
    }

    public function test_case_b_weekly_savings_with_a_twelve_month_deadline(): void
    {
        $plan = $this->project(200, 100, 'week', '2027-10-28');
        $deadline = $plan['deadline'];

        $this->assertSame(52, $deadline['periods_until_deadline']);
        $this->assertSame('5400.00', $deadline['projected_saved_at_deadline']);
        $this->assertSame('4600.00', $deadline['shortfall_at_deadline']);
        $this->assertSame('188.46', $deadline['required_per_period']);
        $this->assertSame('189', $deadline['required_per_period_whole_pesos']);
        $this->assertFalse($deadline['achievable_with_plan']);
        $this->assertSame('short', $deadline['status']);
    }

    public function test_a_deadline_that_the_plan_meets_reports_a_surplus(): void
    {
        $deadline = $this->project(200, 200, 'week', '2027-10-28')['deadline'];

        $this->assertTrue($deadline['achievable_with_plan']);
        $this->assertSame('600.00', $deadline['surplus_at_deadline']);
        $this->assertSame('0.00', $deadline['shortfall_at_deadline']);
    }

    public function test_case_c_an_already_reached_goal_needs_no_more_contributions(): void
    {
        foreach ([10000, 12000] as $saved) {
            $plan = $this->project($saved, 100, 'week');
            $this->assertSame('already_reached', $plan['status']);
            $this->assertSame(0, $plan['contributions_needed']);
            $this->assertSame('0.00', $plan['remaining']);
            $this->assertArrayNotHasKey('scenarios', $plan);
        }

        $user = User::factory()->create();
        Goal::create(['user_id' => $user->id, 'name' => 'Phone', 'target' => 500, 'saved' => 500]);
        $context = $this->context($user, 'if I add 100 every week to my phone goal, when will I reach it?');
        $answer = app(AssistantResponseGuard::class)->goalFallback($context);
        $this->assertStringContainsString('already fully funded', $answer);
        $this->assertStringNotContainsString('98', $answer);
    }

    public function test_case_d_zero_contribution_does_not_divide_by_zero(): void
    {
        $plan = $this->project(200, 0, 'week', '2027-10-28');

        $this->assertSame('no_contribution', $plan['status']);
        $this->assertNull($plan['contributions_needed']);
        $this->assertNull($plan['months_needed']);
        $this->assertNull($plan['completion_date']);
        $this->assertSame('200.00', $plan['projected_saved']);
        $this->assertFalse($plan['reaches_target_in_horizon']);
    }

    public function test_case_e_missing_goal_or_amount_asks_instead_of_inventing(): void
    {
        $user = User::factory()->create();
        $guard = app(AssistantResponseGuard::class);

        $context = $this->context($user, 'Can I buy a new phone if I add ₱100 to my savings goal every week?');
        $this->assertFalse($context['savings_goals']['available']);
        $this->assertNotEmpty($context['savings_goals']['clarification_needed']);
        $this->assertSame([], $context['savings_goals']['goals']);
        $this->assertStringContainsString('target amount', $guard->goalFallback($context));
        $this->assertSame(
            $guard->goalFallback($context),
            $guard->guardGoalAnswer('Your goal is ₱10,000 and you have saved ₱2,000.', $context)
        );

        Goal::create(['user_id' => $user->id, 'name' => 'Phone', 'target' => 10000, 'saved' => 200]);
        $context = $this->context($user, 'Can I buy a new phone with my savings goal?');
        $this->assertNotEmpty($context['savings_goals']['clarification_needed']);
        $this->assertStringContainsString('how often', $guard->goalFallback($context));
    }

    public function test_case_f_projections_never_change_or_store_actual_savings(): void
    {
        $user = User::factory()->create();
        $goal = Goal::create(['user_id' => $user->id, 'name' => 'Phone', 'target' => 10000, 'saved' => 200]);

        $context = $this->context($user, 'Can I buy a phone if I add ₱100 to my savings goal every week?');
        $item = $context['savings_goals']['goals'][0];
        $plan = $item['hypothetical_contribution_plan'];

        $this->assertSame('200.00', $item['saved']);
        $this->assertSame('200.00', $plan['actual_saved']);
        $this->assertTrue($plan['is_hypothetical']);
        $this->assertSame('5400.00', $plan['projected_saved']);
        $this->assertSame('per week', $plan['contribution_frequency']);
        $this->assertArrayNotHasKey('monthly_equivalent', $plan);
        $this->assertEquals(200, $goal->fresh()->saved);
        $this->assertSame(0, $user->transactions()->count());
    }

    public function test_the_guard_replaces_a_projection_reported_as_money_already_saved(): void
    {
        $user = User::factory()->create();
        Goal::create(['user_id' => $user->id, 'name' => 'Phone', 'target' => 10000, 'saved' => 200]);
        $context = $this->context($user, 'Can I buy a phone if I add ₱100 to my savings goal every week?');
        $guard = app(AssistantResponseGuard::class);

        $wrong = 'You have already saved ₱5,400.00 of your ₱10,000.00 goal.';
        $fixed = $guard->guardGoalAnswer($wrong, $context);
        $this->assertNotSame($wrong, $fixed);
        $this->assertStringContainsString('₱200.00 saved', $fixed);
        $this->assertStringContainsString('98 contributions', $fixed);
        $this->assertStringContainsString('22.6 months', $fixed);
        $this->assertStringContainsString('not money you have saved', $fixed);

        $this->assertSame($fixed, $guard->guardGoalAnswer('That would take about 30 months and ₱3,000.00.', $context));

        $good = 'You have ₱200.00 saved of ₱10,000.00. Adding ₱100.00 per week would take about 22.6 months, and you would be projected at ₱5,400.00 after 12 months.';
        $this->assertSame($good, $guard->guardGoalAnswer($good, $context));

        // The backend-written answer must itself pass the guard.
        $this->assertSame($fixed, $guard->guardGoalAnswer($fixed, $context));
    }

    public function test_the_fallback_covers_deadline_shortfall_and_whole_peso_rounding(): void
    {
        $user = User::factory()->create();
        Goal::create(['user_id' => $user->id, 'name' => 'Phone', 'target' => 10000, 'saved' => 200, 'deadline' => '2027-10-28']);
        $context = $this->context($user, 'Can I buy a phone if I add ₱100 to my savings goal every week?');
        $guard = app(AssistantResponseGuard::class);

        $plan = $context['savings_goals']['goals'][0]['hypothetical_contribution_plan'];
        $this->assertSame('189', $plan['deadline']['required_per_period_whole_pesos']);

        $text = $guard->goalFallback($context);
        $this->assertStringContainsString('Not by your 2027-10-28 deadline', $text);
        $this->assertStringContainsString('₱4,600.00 short', $text);
        $this->assertStringContainsString('₱189', $text);
        $this->assertSame($text, $guard->guardGoalAnswer($text, $context));
    }

    public function test_case_g_frequencies_use_the_correct_number_of_periods(): void
    {
        $weekly = $this->project(0, 100, 'week', null, 12, 5200);
        $this->assertSame(52, $weekly['contributions_needed']);
        $this->assertSame('12.0', $weekly['months_needed']);
        $this->assertSame('5200.00', $weekly['projected_saved']);

        $biweekly = $this->project(0, 100, 'biweek', null, 12, 5200);
        $this->assertSame(52, $biweekly['contributions_needed']);
        $this->assertSame('24.0', $biweekly['months_needed']);
        $this->assertSame(26, $biweekly['contributions_in_horizon']);
        $this->assertSame('2600.00', $biweekly['projected_saved']);
        $this->assertSame('every 2 weeks', $biweekly['contribution_frequency']);

        $monthly = $this->project(200, 100, 'month', null, 12, 1500);
        $this->assertSame(13, $monthly['contributions_needed']);
        $this->assertSame('13.0', $monthly['months_needed']);
        $this->assertSame('1400.00', $monthly['projected_saved']);
        $this->assertSame('2027-11-28', $monthly['completion_date']);

        $monthlyDeadline = $this->project(200, 100, 'month', '2027-10-28', 12, 1500)['deadline'];
        $this->assertSame(12, $monthlyDeadline['periods_until_deadline']);
        $this->assertSame('108.33', $monthlyDeadline['required_per_period']);
        $this->assertSame('109', $monthlyDeadline['required_per_period_whole_pesos']);
    }

    public function test_contribution_frequency_is_parsed_from_the_question(): void
    {
        $user = User::factory()->create();
        Goal::create(['user_id' => $user->id, 'name' => 'Phone', 'target' => 10000, 'saved' => 200]);

        foreach ([
            'if I save 100 every two weeks, when will I reach my goal?' => 'every 2 weeks',
            'if I save 100 biweekly, when will I reach my goal?' => 'every 2 weeks',
            'if I add 500 monthly to my goal, how long will it take?' => 'per month',
            'if I add 100 every week to my goal, how long will it take?' => 'per week',
        ] as $question => $label) {
            $plan = $this->context($user, $question)['savings_goals']['goals'][0]['hypothetical_contribution_plan'];
            $this->assertSame($label, $plan['contribution_frequency'], $question);
        }
    }

    public function test_goal_visuals_are_built_from_backend_projections_and_labelled_simulated(): void
    {
        $user = User::factory()->create();
        Goal::create(['user_id' => $user->id, 'name' => 'Phone', 'target' => 10000, 'saved' => 200]);
        $context = $this->context($user, 'Can I buy a phone if I add ₱100 to my savings goal every week?');

        $charts = app(\App\Services\AssistantVisuals::class)->fromContext($context, 'phone goal');
        $titles = array_column($charts, 'title');
        $this->assertContains('Phone: saved vs projected vs target', $titles);
        $this->assertContains('Phone: projected completion timeline', $titles);
        foreach ($charts as $chart) {
            $this->assertSame('simulation', $chart['kind']);
        }

        $line = collect($charts)->firstWhere('type', 'line');
        $this->assertSame(200.0, $line['datasets'][0]['data'][0]);
        $this->assertSame(10000.0, end($line['datasets'][0]['data']));
    }
}
