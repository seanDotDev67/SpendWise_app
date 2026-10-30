<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VisualInsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.assistant.provider' => 'ollama',
            'services.ollama.base_url' => 'http://localhost:11434',
            'services.ollama.model' => 'llama3.2:3b',
        ]);
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));

        $this->user = User::factory()->create();
        $this->seedFor($this->user, 1);
    }

    private function seedFor(User $user, int $multiplier, string $suffix = ''): void
    {
        $food = $user->categories()->create(['name' => 'Food'.$suffix, 'type' => 'expense']);
        $shopping = $user->categories()->create(['name' => 'Shopping'.$suffix, 'type' => 'expense']);
        $salary = $user->categories()->create(['name' => 'Salary'.$suffix, 'type' => 'income']);
        $rows = [
            [$salary, 'income', 5000, '2026-10-01'],
            [$food, 'expense', 1000, '2026-10-12'],
            [$shopping, 'expense', 400, '2026-10-20'],
            [$salary, 'income', 4000, '2026-09-01'],
            [$food, 'expense', 600, '2026-09-10'],
            [$shopping, 'expense', 200, '2026-09-15'],
        ];
        foreach ($rows as [$category, $type, $amount, $date]) {
            $user->transactions()->create([
                'category_id' => $category->id, 'type' => $type, 'amount' => $amount * $multiplier, 'date' => $date,
            ]);
        }
    }

    private function fakeModel(array $pieces): void
    {
        $lines = array_map(fn (string $piece): string => json_encode([
            'message' => ['role' => 'assistant', 'content' => $piece], 'done' => false,
        ]), $pieces);
        $lines[] = json_encode(['message' => ['role' => 'assistant', 'content' => ''], 'done' => true, 'done_reason' => 'stop']);

        Http::fake([
            'localhost:11434/api/tags' => Http::response(['models' => [['name' => 'llama3.2:3b', 'model' => 'llama3.2:3b']]]),
            'localhost:11434/api/chat' => Http::response(implode("\n", $lines)."\n"),
        ]);
    }

    /** @return array<string, mixed> The final stream event. */
    private function ask(string $question, ?User $user = null, array $pieces = ['Here is what your records show.']): array
    {
        $this->fakeModel($pieces);
        $response = $this->actingAs($user ?? $this->user)->post(route('reports.assistant.stream'), [
            'question' => $question,
            'page' => 'reports',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        return $this->done($response);
    }

    private function done(TestResponse $response): array
    {
        $events = array_map(
            fn (string $line): array => json_decode($line, true),
            array_values(array_filter(explode("\n", $response->streamedContent())))
        );

        return end($events);
    }

    private function sentContext(): string
    {
        $request = Http::recorded()
            ->map(fn (array $pair): Request => $pair[0])
            ->first(fn (Request $request): bool => str_ends_with($request->url(), '/api/chat'));

        return json_encode($request->data()['messages']);
    }

    public function test_last_six_months_returns_a_verified_monthly_line_chart(): void
    {
        $done = $this->ask('Show my expenses for the last six months.');

        $chart = $done['insight']['charts'][0];
        $this->assertSame('line', $chart['type']);
        $this->assertSame(['May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026', 'Oct 2026'], $chart['labels']);
        $this->assertSame([0, 0, 0, 0, 800, 1400], $chart['datasets'][1]['data']);
        $this->assertSame($done['insight']['charts'], $done['visuals']);
    }

    public function test_income_and_expense_comparison_uses_calculated_totals(): void
    {
        $done = $this->ask('Compare my income and expenses.');

        $charts = $done['insight']['charts'];
        $this->assertSame(['comparison', 'comparison'], array_column($charts, 'type'));
        $this->assertSame([4000, 800], $charts[0]['datasets'][0]['data']);
        $this->assertSame([5000, 1400], $charts[0]['datasets'][1]['data']);
        $this->assertStringContainsString('₱5,000.00', $done['insight']['summary']);
        $this->assertContains('Food spending rose by ₱400.00 (67%) compared with the previous period.', $done['insight']['insights']);
        $this->assertSame('warning', $done['insight']['severity']);
    }

    public function test_category_question_returns_share_progress_and_donut(): void
    {
        $done = $this->ask('How much did I spend on food this month?');

        $charts = $done['insight']['charts'];
        $this->assertSame(['progress', 'doughnut'], array_column($charts, 'type'));
        $this->assertSame([71], $charts[0]['datasets'][0]['data']);
        $this->assertSame(['Food', 'Shopping'], $charts[1]['labels']);
        $this->assertContains('Food: ₱1,000.00 across 1 transaction(s), 71% of your expenses.', $done['insight']['insights']);
    }

    public function test_daily_trend_is_zero_filled_from_real_transactions(): void
    {
        $done = $this->ask('Show my daily spending trend.');

        $chart = $done['insight']['charts'][0];
        $this->assertSame('line', $chart['type']);
        $this->assertSame('Daily spending', $chart['title']);
        $this->assertSame(1000, $chart['datasets'][0]['data'][array_search('Oct 12', $chart['labels'], true)]);
        $this->assertSame(400, $chart['datasets'][0]['data'][array_search('Oct 20', $chart['labels'], true)]);
        $this->assertEquals(1400, array_sum($chart['datasets'][0]['data']));
    }

    public function test_budget_utilization_uses_income_and_clarifies_no_budgets_exist(): void
    {
        $done = $this->ask('How much of my income is used up against my budget?');

        $progress = collect($done['insight']['charts'])->firstWhere('type', 'progress');
        $this->assertSame([28, 72], $progress['datasets'][0]['data']);
        $this->assertStringContainsString('separate from category budget limits', $progress['note']);
        $this->assertContains('No monthly category budgets have been set yet.', $done['insight']['insights']);
    }

    public function test_budget_question_uses_saved_monthly_limits_and_current_month_transactions(): void
    {
        $food = $this->user->categories()->where('name', 'Food')->firstOrFail();
        Budget::create(['user_id' => $this->user->id, 'category_id' => $food->id, 'amount' => 800]);
        $other = User::factory()->create();
        $private = $other->categories()->create(['name' => 'Private Budget', 'type' => 'expense']);
        Budget::create(['user_id' => $other->id, 'category_id' => $private->id, 'amount' => 9999]);

        $done = $this->ask(
            'Am I staying within my monthly budget?',
            null,
            ['Food is over by ₱200.00 against an ₱800.00 limit.']
        );

        $this->assertFalse($done['insight']['insufficient_data']);
        $chart = $done['insight']['charts'][0];
        $this->assertSame('progress', $chart['type']);
        $this->assertSame(['Food'], $chart['labels']);
        $this->assertSame([125], $chart['datasets'][0]['data']);
        $this->assertStringContainsString('₱800.00 limit', $done['insight']['summary']);
        $this->assertContains('Food: ₱1,000.00 of ₱800.00 used (125%) for October 2026.', $done['insight']['insights']);
        $this->assertSame('warning', $done['insight']['severity']);
        $this->assertFalse($done['insight']['unverified_figures']);
        $this->assertStringContainsString('monthly_limit', $this->sentContext());
        $this->assertStringContainsString('800.00', $this->sentContext());
        $this->assertStringNotContainsString('Private Budget', $this->sentContext());
    }

    public function test_saved_budgets_are_visible_even_before_the_first_transaction(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Rent', 'type' => 'expense']);
        Budget::create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 1200]);

        $done = $this->ask('How is my budget looking?', $user);

        $this->assertFalse($done['insight']['insufficient_data']);
        $this->assertSame('progress', $done['insight']['charts'][0]['type']);
        $this->assertSame(['Rent'], $done['insight']['charts'][0]['labels']);
        $this->assertSame([0], $done['insight']['charts'][0]['datasets'][0]['data']);
        $this->assertContains('Rent: ₱0.00 of ₱1,200.00 used (0%) for October 2026.', $done['insight']['insights']);
    }

    public function test_what_if_questions_are_labelled_as_simulations_and_never_mixed_with_records(): void
    {
        $done = $this->ask('What happens if I reduce my shopping expenses by 15%?');

        $chart = $done['insight']['charts'][0];
        $this->assertSame('simulation', $chart['kind']);
        $this->assertSame([400, 3600], $chart['datasets'][0]['data']);
        $this->assertSame([340, 3660], $chart['datasets'][1]['data']);
        $this->assertStringContainsString('not a recorded result', $chart['note']);
        $this->assertStringContainsString('is_hypothetical', $this->sentContext());
        $this->assertStringStartsWith('Projection:', $done['insight']['insights'][0]);
    }

    public function test_other_users_data_never_reaches_charts_or_the_model(): void
    {
        $other = User::factory()->create();
        $this->seedFor($other, 100, 'Secret');

        $done = $this->ask('Where does most of my money go?');

        $this->assertStringNotContainsString('Secret', json_encode($done));
        $this->assertStringNotContainsString('Secret', $this->sentContext());
        $this->assertSame([1000, 400], $done['insight']['charts'][1]['datasets'][0]['data']);
    }

    public function test_another_users_category_name_is_not_resolved(): void
    {
        $other = User::factory()->create();
        $other->categories()->create(['name' => 'Yachts', 'type' => 'expense']);

        $this->ask('How much did I spend on yachts?');

        $this->assertStringNotContainsString('category_focus', $this->sentContext());
    }

    public function test_insufficient_data_returns_an_empty_state_without_charts(): void
    {
        $done = $this->ask('How much did I spend on food?', User::factory()->create());

        $this->assertTrue($done['insight']['insufficient_data']);
        $this->assertSame([], $done['insight']['charts']);
        $this->assertSame([], $done['visuals']);
    }

    public function test_model_json_is_unwrapped_and_model_supplied_charts_are_ignored(): void
    {
        $json = json_encode([
            'message' => 'Food leads your spending.',
            'recommendations' => ['Plan weekly meals.'],
            'charts' => [['type' => 'pie', 'title' => 'x', 'labels' => ['a'], 'datasets' => [['label' => 'a', 'data' => [999999]]]]],
        ]);

        $done = $this->ask('Where does most of my money go?', null, [substr($json, 0, 40), substr($json, 40)]);

        $this->assertSame('Food leads your spending.', $done['answer']);
        $this->assertSame('Food leads your spending.', $done['insight']['message']);
        $this->assertSame('Plan weekly meals.', $done['insight']['recommendations'][0]);
        $this->assertStringNotContainsString('999999', json_encode($done['insight']['charts']));
        $this->assertNotContains('pie', array_column($done['insight']['charts'], 'type'));
    }

    public function test_malformed_model_json_falls_back_gracefully(): void
    {
        $done = $this->ask('Where does most of my money go?', null, ['{"message": "Food is your top category.", "recommend']);

        $this->assertSame('Food is your top category.', $done['insight']['message']);
        $this->assertNotEmpty($done['insight']['charts']);
    }

    public function test_figures_not_found_in_records_are_flagged_but_charts_stay_verified(): void
    {
        $done = $this->ask('How much did I spend on food?', null, ['You spent ₱77,777.00 on food.']);

        $this->assertTrue($done['insight']['unverified_figures']);
        $this->assertStringNotContainsString('77777', json_encode($done['insight']['charts']));
    }

    public function test_general_questions_get_no_structured_insight(): void
    {
        $done = $this->ask('What is a PHP interface?');

        $this->assertNull($done['insight']);
        $this->assertSame([], $done['visuals']);
    }

    public function test_provider_failure_returns_an_error_event_and_no_charts(): void
    {
        Http::fake(['localhost:11434/*' => fn () => throw new ConnectionException('Connection refused')]);

        $done = $this->done($this->actingAs($this->user)->post(route('reports.assistant.stream'), [
            'question' => 'Show my daily spending trend.',
        ]));

        $this->assertSame('error', $done['type']);
        $this->assertArrayNotHasKey('insight', $done);
    }

    public function test_reports_page_still_renders_with_the_assistant(): void
    {
        $this->actingAs($this->user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Show my expenses for the last six months.');
    }
}
