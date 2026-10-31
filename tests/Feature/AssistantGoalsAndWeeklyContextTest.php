<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use App\Services\AssistantFinancialContext;
use App\Services\AssistantResponseGuard;
use App\Services\FinancialInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AssistantGoalsAndWeeklyContextTest extends TestCase
{
    use RefreshDatabase;

    private function context(User $user, string $question): ?array
    {
        return app(AssistantFinancialContext::class)->forQuestion(
            $user,
            $question,
            [],
            now()->startOfMonth(),
            now()
        );
    }

    public function test_goal_questions_include_goals_and_a_ranged_estimate(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense', 'color' => 'rose']);
        Goal::create(['user_id' => $user->id, 'name' => 'Laptop', 'target' => 1000, 'saved' => 250, 'deadline' => '2027-01-28']);
        $user->transactions()->create(['category_id' => $category->id, 'amount' => 3000, 'type' => 'income', 'date' => '2026-10-10']);
        $user->transactions()->create(['category_id' => $category->id, 'amount' => 1000, 'type' => 'expense', 'date' => '2026-10-12']);

        $context = $this->context($user, 'If I continue like this, when will I reach my goals?');

        $goals = $context['savings_goals'];
        $this->assertSame('Laptop', $goals['goals'][0]['name']);
        $this->assertSame('750.00', $goals['goals'][0]['remaining']);
        $this->assertSame('2000.00', $goals['monthly_saving_pace']['highest']);
        $this->assertSame('0.4', $goals['goals'][0]['estimated_months_to_reach']['fastest']);
        $this->assertNull($goals['goals'][0]['estimated_months_to_reach']['slowest']);

        $guard = app(AssistantResponseGuard::class);
        $this->assertFalse($guard->hasUnsupportedNumericClaims('You have saved ₱250.00 of ₱1000.00.', $context));
    }

    public function test_weekly_summary_covers_the_last_seven_days(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense', 'color' => 'rose']);
        $user->transactions()->create(['category_id' => $category->id, 'amount' => 70, 'type' => 'expense', 'date' => '2026-10-27']);
        $user->transactions()->create(['category_id' => $category->id, 'amount' => 40, 'type' => 'expense', 'date' => '2026-10-18']);
        $user->transactions()->create(['category_id' => $category->id, 'amount' => 999, 'type' => 'expense', 'date' => '2026-09-01']);

        $context = $this->context($user, 'Give me a 1 week summary of my spending habits');

        $week = $context['weekly_summary'];
        $this->assertSame('2026-10-22', $week['period']['start']);
        $this->assertSame('70.00', $week['expenses']);
        $this->assertSame('10.00', $week['average_daily_spend']);
        $this->assertSame('40.00', $week['previous_week']['expenses']);
        $this->assertCount(7, $week['daily_spending']);
        $this->assertSame('70.00', $context['recorded_totals']['expenses']);
    }

    public function test_added_amount_what_if_projects_a_named_goal(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $user = User::factory()->create();
        Goal::create(['user_id' => $user->id, 'name' => 'Laptop', 'target' => 1500, 'saved' => 200]);
        Goal::create(['user_id' => $user->id, 'name' => 'Trip', 'target' => 5000, 'saved' => 0]);

        $context = $this->context($user, 'if i added 100 every month for my laptop goal can i achieve it in the next year?');

        $goals = collect($context['savings_goals']['goals'])->keyBy('name');
        $plan = $goals['Laptop']['hypothetical_contribution_plan'];
        $this->assertSame('1200.00', $plan['projected_added']);
        $this->assertSame('1400.00', $plan['projected_saved']);
        $this->assertFalse($plan['reaches_target_in_horizon']);
        $this->assertSame('100.00', $plan['shortfall']);
        $this->assertSame('13.0', $plan['months_needed']);
        $this->assertArrayNotHasKey('hypothetical_contribution_plan', $goals['Trip']);

        $weekly = $this->context($user, 'if I save 100 every week for my laptop goal, can I reach it in 6 months?');
        $this->assertSame('2600.00', collect($weekly['savings_goals']['goals'])->firstWhere('name', 'Laptop')['hypothetical_contribution_plan']['projected_added']);
    }

    public function test_goal_and_weekly_answers_use_topic_specific_charts_and_advice(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense', 'color' => 'rose']);
        Goal::create(['user_id' => $user->id, 'name' => 'Laptop', 'target' => 1500, 'saved' => 200]);
        $user->transactions()->create(['category_id' => $category->id, 'amount' => 70, 'type' => 'expense', 'date' => '2026-10-27']);
        $user->transactions()->create(['category_id' => $category->id, 'amount' => 20, 'type' => 'expense', 'date' => '2026-10-20']);
        $service = app(FinancialInsightService::class);

        $plan = $service->build($this->context($user, 'if i added 100 every month for my laptop goal can i achieve it in the next year?'), 'laptop', 'Not quite.');
        $this->assertSame(['comparison'], array_column($plan['charts'], 'type'));
        $this->assertSame('simulation', $plan['charts'][0]['kind']);
        $this->assertSame([200.0, 1400.0, 1500.0], $plan['charts'][0]['datasets'][0]['data']);
        $this->assertStringContainsString('short', implode(' ', $plan['insights']));
        $this->assertStringNotContainsString('largest expense category', implode(' ', $plan['insights']));

        $week = $service->build($this->context($user, 'give my 1 week spending report'), 'week', 'Here is your week.');
        $this->assertSame(['line', 'bar'], array_column($week['charts'], 'type'));
        $this->assertSame('Spending over the last 7 days', $week['charts'][0]['title']);
        $this->assertStringContainsString('busiest day', implode(' ', $week['insights']));

        $generic = $service->build($this->context($user, 'where does my money go?'), 'money', 'Mostly food.');
        $this->assertNotSame($week['insights'], $generic['insights']);
        $this->assertStringContainsString('largest expense category', implode(' ', $generic['insights']));
    }

    public function test_unrelated_questions_still_get_no_financial_context(): void
    {
        $this->assertNull($this->context(User::factory()->create(), 'What is the capital of France?'));
        $this->assertNull($this->context(User::factory()->create(), 'Write a report on the French revolution'));
    }

    public function test_varied_weekly_report_phrasings_load_the_data(): void
    {
        $user = User::factory()->create();

        foreach ([
            'give my 1 week spending report',
            'Show me my weekly spending report',
            'can i get a spending report for this week',
            'summary of my last 7 days spending',
        ] as $question) {
            $this->assertArrayHasKey('weekly_summary', $this->context($user, $question) ?? [], $question);
        }
    }
}
