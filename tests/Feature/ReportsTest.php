<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => null]);
    }

    public function test_report_summaries_and_charts_use_only_transactions_in_the_selected_range(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $incomeCategory = $user->categories()->create(['name' => 'Salary', 'type' => 'income']);
        $foodCategory = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $otherCategory = $otherUser->categories()->create(['name' => 'Private', 'type' => 'expense']);

        $user->transactions()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 5000,
            'type' => 'income',
            'description' => 'Payday',
            'date' => '2026-10-10',
        ]);
        $user->transactions()->create([
            'category_id' => $foodCategory->id,
            'amount' => 1200.50,
            'type' => 'expense',
            'description' => 'Groceries',
            'date' => '2026-10-12',
        ]);
        $user->transactions()->create([
            'category_id' => $foodCategory->id,
            'amount' => 300,
            'type' => 'expense',
            'description' => 'Previous month meal',
            'date' => '2026-09-30',
        ]);
        $otherUser->transactions()->create([
            'category_id' => $otherCategory->id,
            'amount' => 9000,
            'type' => 'expense',
            'description' => 'Private purchase',
            'date' => '2026-10-15',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertSee('₱5,000.00')
            ->assertSee('₱1,200.50')
            ->assertSee('₱3,799.50')
            ->assertSee('Groceries')
            ->assertSee('Payday')
            ->assertDontSee('Previous month meal')
            ->assertDontSee('Private purchase')
            ->assertDontSee('9,000.00');
    }

    public function test_assistant_answers_from_selected_user_data_and_explains_unavailable_budgets(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Transport', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 45.25,
            'type' => 'expense',
            'description' => 'Bus fare',
            'date' => '2026-10-12',
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'Which category has the highest spending?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Looks like Transport is your biggest spending category for Oct 1, 2026 to Oct 31, 2026: ₱45.25 (100% of recorded expenses). Want to explore what’s included in that category?');

        $this->postJson(route('reports.assistant'), [
            'question' => 'Am I staying within my budget?',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])
            ->assertOk()
            ->assertJsonPath('answer', 'I’d be happy to check that, but budgets aren’t saved in SpendWise yet, so I can’t tell whether you’re within one. I can still walk you through your recorded income and spending for Oct 1, 2026 to Oct 31, 2026.');
    }

    public function test_reports_have_clear_empty_states_and_chat_does_not_invent_totals(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.index', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertSee('No transactions recorded in this date range.')
            ->assertSee('No monthly trend is available until transactions are recorded.');

        $this->postJson(route('reports.assistant'), [
            'question' => 'How much did I spend this month?',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])
            ->assertOk()
            ->assertJsonPath('answer', 'Absolutely — for Oct 1, 2026 to Oct 31, 2026, you recorded ₱0.00 in income and ₱0.00 in expenses, leaving a net cash flow of ₱0.00 across 0 transactions. Is there a category or month you’d like to dig into?');
    }

    public function test_report_date_range_and_assistant_questions_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('reports.index', [
                'start_date' => '2026-11-01',
                'end_date' => '2026-10-01',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_date');

        $this->postJson(route('reports.assistant'), [
            'question' => str_repeat('x', 501),
            'start_date' => 'not-a-date',
            'end_date' => '2026-10-01',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question', 'start_date']);
    }

    public function test_reports_and_assistant_require_authentication(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->postJson(route('reports.assistant'), ['question' => 'How much did I spend?'])
            ->assertUnauthorized();
    }

    public function test_gemini_receives_aggregate_report_data_and_adds_only_a_qualitative_explanation(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-3.6-flash',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [[
                        'text' => 'Food is your largest recorded expense category. Reviewing recurring purchases there may help you find practical ways to spend less.',
                    ]]],
                ]],
            ]),
        ]);

        $user = User::factory()->create(['name' => 'Private Account Name']);
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 80,
            'type' => 'expense',
            'description' => 'Private transaction description',
            'date' => '2026-10-12',
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'How can I spend less?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'A good place to start might be Food, your largest recorded expense category at ₱80.00 for Oct 1, 2026 to Oct 31, 2026. You could review recurring purchases there and see whether anything is easy to trim. I won’t guess at savings, but I can help you think through options.'."\n\n".'Food is your largest recorded expense category. Reviewing recurring purchases there may help you find practical ways to spend less.');

        Http::assertSentCount(1);
        [$request] = Http::recorded()->first();
        $payload = json_encode($request->data());

        $this->assertStringContainsString('models/gemini-3.6-flash:generateContent', $request->url());
        $this->assertTrue($request->hasHeader('x-goog-api-key', 'test-gemini-key'));
        $this->assertStringContainsString('80.00', $payload);
        $this->assertStringContainsString('Food', $payload);
        $this->assertStringNotContainsString('Private transaction description', $payload);
        $this->assertStringNotContainsString('Private Account Name', $payload);
    }

    public function test_gemini_numeric_claims_are_rejected_in_favor_of_verified_server_totals(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [[
                        'text' => 'Reduce spending by 50 percent to save more.',
                    ]]],
                ]],
            ]),
        ]);

        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 80,
            'type' => 'expense',
            'date' => '2026-10-12',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Gemini report assistant returned an invalid or ungrounded response.');

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'How can I spend less?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'A good place to start might be Food, your largest recorded expense category at ₱80.00 for Oct 1, 2026 to Oct 31, 2026. You could review recurring purchases there and see whether anything is easy to trim. I won’t guess at savings, but I can help you think through options.');
    }

    public function test_gemini_api_failure_is_logged_and_preserves_the_verified_fallback(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'unavailable'], 503),
        ]);
        Log::shouldReceive('warning')
            ->once()
            ->with('Gemini report assistant returned an unsuccessful response.', ['status' => 503]);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'How much did I spend?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Absolutely — for Oct 1, 2026 to Oct 31, 2026, you recorded ₱0.00 in income and ₱0.00 in expenses, leaving a net cash flow of ₱0.00 across 0 transactions. Is there a category or month you’d like to dig into?');
    }

    public function test_missing_gemini_api_key_uses_the_verified_fallback_without_a_request(): void
    {
        config(['services.gemini.api_key' => null]);
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), [
                'question' => 'How much did I spend?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Absolutely — for Oct 1, 2026 to Oct 31, 2026, you recorded ₱0.00 in income and ₱0.00 in expenses, leaving a net cash flow of ₱0.00 across 0 transactions. Is there a category or month you’d like to dig into?');

        Http::assertNothingSent();
    }

    public function test_greetings_and_thanks_receive_friendly_replies_without_calling_gemini(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        $this->travelTo(Carbon::parse('2026-10-28 09:00:00', 'Asia/Manila'));
        Http::fake();
        $user = User::factory()->create(['name' => 'Juan Dela Cruz']);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'Hi there!',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Good morning, Juan! 👋 I’m glad you stopped by. I’m SpendWise AI, and I’m here to help make sense of your finances. Want to look at your spending, see which categories are adding up, or talk about ways to save?');

        $this->postJson(route('reports.assistant'), [
            'question' => 'Thank you!',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])
            ->assertOk()
            ->assertJsonPath('answer', 'You’re very welcome! I’m glad I could help. If you want to explore another part of your spending, just ask and we can look at it together.');

        Http::assertNothingSent();
    }

    public function test_greeting_uses_the_users_first_name_and_application_local_time(): void
    {
        config(['app.timezone' => 'Asia/Manila']);
        $this->travelTo(Carbon::parse('2026-10-28 07:30:00', 'Asia/Manila'));
        $user = User::factory()->create(['name' => 'Maria Santos']);
        $this->actingAs($user);

        foreach ([
            ['2026-10-28 07:30:00', 'Good morning'],
            ['2026-10-28 13:15:00', 'Good afternoon'],
            ['2026-10-28 19:45:00', 'Good evening'],
        ] as [$localTime, $expectedGreeting]) {
            $this->travelTo(Carbon::parse($localTime, 'Asia/Manila'));

            $this->postJson(route('reports.assistant'), [
                'question' => 'Hello!',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
                ->assertOk()
                ->assertJsonPath('answer', $expectedGreeting.', Maria! 👋 I’m glad you stopped by. I’m SpendWise AI, and I’m here to help make sense of your finances. Want to look at your spending, see which categories are adding up, or talk about ways to save?');

            $this->get(route('reports.index', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]))
                ->assertOk()
                ->assertSee($expectedGreeting.', Maria! 👋', false)
                ->assertSee('I’m glad you’re here. What would you like to explore about your spending today?', false);
        }
    }
}
