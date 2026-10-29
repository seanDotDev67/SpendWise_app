<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SpendingHabitAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SpendingHabitTest extends TestCase
{
    use RefreshDatabase;

    private function spend(User $user, string $category, float $amount, string $date): void
    {
        $model = $user->categories()->firstOrCreate(['name' => $category], ['type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $model->id,
            'amount' => $amount,
            'type' => 'expense',
            'date' => $date,
        ]);
    }

    public function test_no_pattern_is_claimed_without_enough_history(): void
    {
        $user = User::factory()->create();
        $this->spend($user, 'Food', 50, '2026-10-10');

        $result = app(SpendingHabitAnalyzer::class)->analyze($user, Carbon::parse('2026-10-20'));

        $this->assertNull($result['lead']);
        $this->assertSame([], $result['signals']);
    }

    public function test_faster_spending_than_the_same_days_last_month_is_the_lead_signal(): void
    {
        $user = User::factory()->create();
        $this->spend($user, 'Food', 100, '2026-09-05');
        $this->spend($user, 'Food', 300, '2026-10-05');

        $lead = app(SpendingHabitAnalyzer::class)->analyze($user, Carbon::parse('2026-10-20'))['lead'];

        $this->assertSame('pace', $lead['type']);
        $this->assertStringContainsString('up 200%', $lead['message']);
        $this->assertSame(100.0, $lead['chart']['rows'][0]['value']);
        $this->assertSame(300.0, $lead['chart']['rows'][1]['value']);
    }

    public function test_spending_after_the_comparable_day_last_month_is_not_counted(): void
    {
        $user = User::factory()->create();
        $this->spend($user, 'Food', 100, '2026-09-05');
        $this->spend($user, 'Food', 5000, '2026-09-28');
        $this->spend($user, 'Food', 105, '2026-10-05');

        $signals = collect(app(SpendingHabitAnalyzer::class)->analyze($user, Carbon::parse('2026-10-20'))['signals']);

        $this->assertNull($signals->firstWhere('type', 'pace'));
    }

    public function test_a_climbing_category_is_identified(): void
    {
        $user = User::factory()->create();
        $this->spend($user, 'Food', 200, '2026-09-04');
        $this->spend($user, 'Transport', 200, '2026-09-04');
        $this->spend($user, 'Food', 200, '2026-10-04');
        $this->spend($user, 'Transport', 215, '2026-10-04');
        $this->spend($user, 'Shopping', 1, '2026-10-04');
        $this->spend($user, 'Food', 180, '2026-10-06');

        $signals = collect(app(SpendingHabitAnalyzer::class)->analyze($user, Carbon::parse('2026-10-20'))['signals']);

        $shift = $signals->firstWhere('type', 'category_shift');
        $this->assertSame('Food is climbing', $shift['title']);
    }

    public function test_a_dominant_weekday_is_found_with_a_chart_and_other_users_are_ignored(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        // 2026-10-03, 10, 17 are Saturdays.
        foreach (['2026-10-03', '2026-10-10', '2026-10-17'] as $saturday) {
            $this->spend($user, 'Food', 500, $saturday);
        }
        foreach (range(1, 10) as $i) {
            $this->spend($user, 'Misc', 20, '2026-10-'.str_pad((string) (($i % 5) + 5), 2, '0', STR_PAD_LEFT));
        }
        foreach (range(1, 20) as $i) {
            $this->spend($other, 'Food', 9999, '2026-10-12');
        }

        $weekday = collect(app(SpendingHabitAnalyzer::class)->analyze($user, Carbon::parse('2026-10-20'))['signals'])
            ->firstWhere('type', 'weekday');

        $this->assertSame('Saturday is your biggest spending day', $weekday['title']);
        $this->assertSame('bars', $weekday['chart']['kind']);
        $this->assertCount(7, $weekday['chart']['rows']);
        $this->assertTrue($weekday['chart']['rows'][5]['highlight']);
        $this->assertLessThan(10000, array_sum(array_column($weekday['chart']['rows'], 'value')));
    }

    public function test_sparse_logging_is_reported_for_established_users(): void
    {
        $user = User::factory()->create();
        $this->spend($user, 'Food', 10, '2026-08-01');
        $this->spend($user, 'Food', 10, '2026-10-18');

        $lead = app(SpendingHabitAnalyzer::class)->analyze($user, Carbon::parse('2026-10-20'))['lead'];

        $this->assertSame('consistency', $lead['type']);
        $this->assertSame(1, $lead['chart']['filled']);
    }

    public function test_dashboard_shows_the_users_own_pattern_visual_and_nothing_for_new_users(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('Your pattern');

        $this->spend($user, 'Food', 100, '2026-09-05');
        $this->spend($user, 'Food', 300, '2026-10-05');

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Your pattern')
            ->assertSee('You’re spending faster than last month')
            ->assertSee('data-habit="pace"', false);

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertDontSee('Your pattern');
    }
}
