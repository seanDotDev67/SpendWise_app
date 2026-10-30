<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
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

        config([
            'services.assistant.provider' => 'gemini',
            'services.gemini.api_key' => null,
        ]);
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

    public function test_reports_default_to_the_first_day_of_the_current_month_through_today(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);

        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 80,
            'type' => 'expense',
            'description' => 'This month',
            'date' => '2026-10-05',
        ]);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 40,
            'type' => 'expense',
            'description' => 'Last month',
            'date' => '2026-09-30',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('name="start_date" value="2026-10-01"', false)
            ->assertSee('name="end_date" value="2026-10-28"', false)
            ->assertSee('₱80.00')
            ->assertSee('This month')
            ->assertDontSee('Last month');
    }

    public function test_category_charts_use_the_saved_user_category_colors(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $user = User::factory()->create();
        $food = $user->categories()->create([
            'name' => 'Food',
            'type' => 'expense',
            'color' => 'rose',
        ]);
        $transport = $user->categories()->create([
            'name' => 'Transport',
            'type' => 'expense',
            'color' => '#123abc',
        ]);

        $user->transactions()->create([
            'category_id' => $food->id,
            'amount' => 100,
            'type' => 'expense',
            'description' => 'Lunch',
            'date' => '2026-10-12',
        ]);
        $user->transactions()->create([
            'category_id' => $transport->id,
            'amount' => 50,
            'type' => 'expense',
            'description' => 'Bus fare',
            'date' => '2026-10-13',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertSee('style="background: conic-gradient(#d9b9bd', false)
            ->assertSee('style="background: #d9b9bd"', false)
            ->assertSee('style="background: #123abc"', false)
            ->assertSee('class="report-top-fill" style="width: 67%; background: #d9b9bd"', false);
    }

    public function test_assistant_answers_from_selected_user_data_and_explains_unavailable_budgets(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => 'Transport is your largest recorded expense. You could review regular fares and look for lower-cost options that still work for you.']]],
                    ]],
                ])
                ->push([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => 'I can’t check budget use because SpendWise does not have saved budget records yet. I can still help you review your recorded spending.']]],
                    ]],
                ]),
        ]);
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
            ->assertJsonPath('answer', 'Transport is your largest recorded expense. You could review regular fares and look for lower-cost options that still work for you.');

        $this->postJson(route('reports.assistant'), [
            'question' => 'Am I staying within my budget?',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])
            ->assertOk()
            ->assertJsonPath('answer', 'I can’t check budget use because SpendWise does not have saved budget records yet. I can still help you review your recorded spending.');
    }

    public function test_reports_have_clear_empty_states_and_chat_does_not_invent_totals(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => 'There are no transactions in the selected range yet. Once you record some activity, I can help you make sense of it.']]],
                ]],
            ]),
        ]);
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
            ->assertJsonPath('answer', 'There are no transactions in the selected range yet. Once you record some activity, I can help you make sense of it.');
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
            'question' => str_repeat('x', 1001),
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

    public function test_gemini_receives_minimal_authenticated_aggregate_data_for_financial_questions(): void
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
        $otherUser = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $otherCategory = $otherUser->categories()->create(['name' => 'Other user category', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 80,
            'type' => 'expense',
            'description' => 'Private transaction description',
            'date' => '2026-10-12',
        ]);
        $otherUser->transactions()->create([
            'category_id' => $otherCategory->id,
            'amount' => 9999,
            'type' => 'expense',
            'description' => 'Other user private transaction',
            'date' => '2026-10-12',
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'How can I spend less?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Food is your largest recorded expense category. Reviewing recurring purchases there may help you find practical ways to spend less.');

        Http::assertSentCount(1);
        [$request] = Http::recorded()->first();
        $payload = json_encode($request->data());

        $this->assertStringContainsString('models/gemini-3.6-flash:generateContent', $request->url());
        $this->assertTrue($request->hasHeader('x-goog-api-key', 'test-gemini-key'));
        $this->assertStringContainsString('80.00', $payload);
        $this->assertStringContainsString('Food', $payload);
        $this->assertStringNotContainsString('9999', $payload);
        $this->assertStringNotContainsString('Other user category', $payload);
        $this->assertStringNotContainsString('Private transaction description', $payload);
        $this->assertStringNotContainsString('Private Account Name', $payload);
        $this->assertStringNotContainsString('Other user private transaction', $payload);
    }

    public function test_gemini_unsupported_numeric_financial_claims_are_rejected(): void
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
            ->with('Gemini report assistant returned an invalid or ungrounded response.', [
                'finish_reason' => 'STOP',
                'has_text' => true,
                'too_long' => false,
                'unsupported_financial_number' => true,
            ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'How can I spend less?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'SpendWise AI could not respond right now. Please try again in a moment.');
    }

    public function test_assistant_accepts_numbered_financial_recommendations_without_unverified_amounts(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [[
                        'text' => "1. Track purchases for a short time to see where small costs add up.\n2. Review recurring bills and consider lower-cost options where practical.",
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), [
                'question' => 'How can I reduce my expenses?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', "1. Track purchases for a short time to see where small costs add up.\n2. Review recurring bills and consider lower-cost options where practical.");
    }

    public function test_gemini_api_failure_is_logged_and_returned_as_a_clear_error(): void
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
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'SpendWise AI could not respond right now. Please try again in a moment.');
    }

    public function test_assistant_returns_text_when_gemini_reaches_its_output_token_limit(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'MAX_TOKENS',
                    'content' => ['parts' => [[
                        'text' => 'Start by reviewing recurring costs and choosing one practical change that feels manageable.',
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), [
                'question' => 'How can I reduce my expenses?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Start by reviewing recurring costs and choosing one practical change that feels manageable.');

        [$request] = Http::recorded()->first();
        $this->assertSame(2048, $request->data()['generationConfig']['maxOutputTokens']);
        $this->assertSame('medium', $request->data()['generationConfig']['thinkingConfig']['thinkingLevel']);
        $this->assertArrayNotHasKey('temperature', $request->data()['generationConfig']);
    }

    public function test_missing_gemini_api_key_returns_configuration_help_without_a_request(): void
    {
        config(['services.gemini.api_key' => null]);
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), [
                'question' => 'How much did I spend?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'SpendWise AI is not configured correctly yet. Please ask the administrator to check the AI provider settings.');

        Http::assertNothingSent();
    }

    public function test_greetings_and_general_conversations_use_gemini_naturally(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        $this->travelTo(Carbon::parse('2026-10-28 09:00:00', 'Asia/Manila'));
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => 'Hello! I’m doing well, thanks for asking. What’s on your mind?']]],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), [
                'question' => 'Hi there!',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Hello! I’m doing well, thanks for asking. What’s on your mind?');

        $this->postJson(route('reports.assistant'), [
            'question' => 'What is Laravel MVC?',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])
            ->assertOk()
            ->assertJsonPath('answer', 'Hello! I’m doing well, thanks for asking. What’s on your mind?');

        Http::assertSentCount(2);
    }

    public function test_general_questions_do_not_send_or_retrieve_personal_financial_data(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => 'Laravel MVC separates application data, request handling, and presentation. A model represents data, a controller handles requests, and a view renders the response.']]],
                ]],
            ]),
        ]);
        $user = User::factory()->create(['name' => 'Private Account Name']);
        $category = $user->categories()->create(['name' => 'Private category', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 999,
            'type' => 'expense',
            'description' => 'Private transaction',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'What is Laravel MVC?',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Laravel MVC separates application data, request handling, and presentation. A model represents data, a controller handles requests, and a view renders the response.');

        [$request] = Http::recorded()->first();
        $payload = json_encode($request->data());
        $this->assertStringContainsString('What is Laravel MVC?', $payload);
        $this->assertStringNotContainsString('999', $payload);
        $this->assertStringNotContainsString('Private category', $payload);
        $this->assertStringNotContainsString('Private transaction', $payload);
        $this->assertStringNotContainsString('Private Account Name', $payload);

        $this->postJson(route('reports.assistant'), [
            'question' => 'Explain compound interest.',
        ])->assertOk();

        [$followUpRequest] = Http::recorded()->last();
        $followUpPayload = json_encode($followUpRequest->data());
        $this->assertStringContainsString('Explain compound interest.', $followUpPayload);
        $this->assertStringNotContainsString('Verified financial context', $followUpPayload);
    }

    public function test_follow_up_after_a_general_question_does_not_reuse_older_financial_context(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => 'Review flexible spending when you are ready.']]],
                    ]],
                ])
                ->push([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => 'PHP MVC separates data, request handling, and presentation.']]],
                    ]],
                ])
                ->push([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => 'A model represents data and its rules.']]],
                    ]],
                ]),
        ]);
        $user = User::factory()->create();
        $food = $user->categories()->create(['name' => 'Private food category', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $food->id,
            'amount' => 80,
            'type' => 'expense',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), ['question' => 'How can I spend less?'])
            ->assertOk();
        $this->postJson(route('reports.assistant'), ['question' => 'What is PHP MVC?'])
            ->assertOk();
        $this->postJson(route('reports.assistant'), ['question' => 'Tell me more'])
            ->assertOk();

        $requests = Http::recorded()->map(fn (array $recorded) => $recorded[0]);
        $followUpPayload = json_encode($requests[2]->data());
        $this->assertStringContainsString('Tell me more', $followUpPayload);
        $this->assertStringNotContainsString('Verified financial context', $followUpPayload);
        $this->assertStringNotContainsString('Private food category', $followUpPayload);
    }

    public function test_gemini_uses_configured_endpoint_and_omits_thought_parts_from_the_answer(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.endpoint' => 'https://gemini.example.test/v1beta/models',
        ]);
        Http::fake([
            'gemini.example.test/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [
                        ['thought' => true, 'text' => 'PRIVATE_THOUGHT_CONTENT'],
                        ['text' => 'A model represents application data.'],
                    ]],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'What is a model?'])
            ->assertOk()
            ->assertJsonPath('answer', 'A model represents application data.');

        [$request] = Http::recorded()->first();
        $this->assertSame(
            'https://gemini.example.test/v1beta/models/gemini-3.6-flash:generateContent',
            $request->url()
        );
    }

    public function test_gemini_rejects_non_https_endpoints_without_sending_the_api_key(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.endpoint' => 'http://gemini.example.test/v1beta/models',
        ]);
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'SpendWise AI is not configured correctly yet. Please ask the administrator to check the AI provider settings.');

        Http::assertNothingSent();
    }

    public function test_gemini_peso_claims_are_checked_against_recorded_financial_amounts(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => 'Your recorded expenses are ₱999.']]],
                ]],
            ]),
        ]);
        Log::shouldReceive('warning')
            ->once()
            ->with('Gemini report assistant returned an invalid or ungrounded response.', [
                'finish_reason' => 'STOP',
                'has_text' => true,
                'too_long' => false,
                'unsupported_financial_number' => true,
            ]);

        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 80,
            'type' => 'expense',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'How much did I spend?',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->toDateString(),
            ])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'SpendWise AI could not respond right now. Please try again in a moment.');
    }

    public function test_conversation_history_supports_follow_ups_and_is_cleared_for_a_new_chat(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => 'Your spending is higher in the current period than the previous one.']]],
                    ]],
                ])
                ->push([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => 'You could review flexible purchases in Food and choose a small change that feels sustainable.']]],
                    ]],
                ])
                ->push([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => 'Laravel MVC separates data, request handling, and presentation.']]],
                    ]],
                ]),
        ]);

        $user = User::factory()->create();
        $food = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $food->id,
            'amount' => 120,
            'type' => 'expense',
            'description' => 'Lunches',
            'date' => '2026-10-12',
        ]);
        $user->transactions()->create([
            'category_id' => $food->id,
            'amount' => 80,
            'type' => 'expense',
            'description' => 'Last month groceries',
            'date' => '2026-09-12',
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'Compare my spending this month and last month.',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-28',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Your spending is higher in the current period than the previous one.');

        $this->postJson(route('reports.assistant'), [
            'question' => 'What could I do about that?',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-28',
        ])
            ->assertOk()
            ->assertJsonPath('answer', 'You could review flexible purchases in Food and choose a small change that feels sustainable.');

        $this->postJson(route('reports.assistant'), [
            'question' => 'Now explain Laravel MVC.',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-28',
        ])
            ->assertOk()
            ->assertJsonPath('answer', 'Laravel MVC separates data, request handling, and presentation.');

        $requests = Http::recorded()->map(fn (array $recorded) => $recorded[0]);
        $followUpPayload = json_encode($requests[1]->data());
        $generalPayload = json_encode($requests[2]->data());

        $this->assertStringContainsString('previous_period', $followUpPayload);
        $this->assertStringContainsString('category_comparison', $followUpPayload);
        $this->assertStringContainsString('40.00', $followUpPayload);
        $this->assertStringContainsString('change_percent', $followUpPayload);
        $this->assertStringContainsString('120.00', $followUpPayload);
        $this->assertStringContainsString('80.00', $followUpPayload);
        $this->assertStringContainsString('Compare my spending this month and last month.', $followUpPayload);
        $this->assertStringContainsString('Your spending is higher in the current period', $followUpPayload);
        $this->assertStringNotContainsString('Verified financial context', $generalPayload);

        $this->get(route('reports.index'))
            ->assertOk()
            ->assertSee('What could I do about that?', false)
            ->assertSee('Now explain Laravel MVC.', false)
            ->assertSee('New chat');

        $this->postJson(route('reports.assistant.reset'))
            ->assertOk()
            ->assertJsonPath('reset', true);

        $this->get(route('reports.index'))
            ->assertOk()
            ->assertDontSee('What could I do about that?', false)
            ->assertDontSee('Now explain Laravel MVC.', false);
    }

    public function test_financial_chat_history_is_isolated_between_authenticated_users(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => 'I can help with that.']]],
                ]],
            ]),
        ]);

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($firstUser)
            ->postJson(route('reports.assistant'), ['question' => 'Hello there'])
            ->assertOk();

        $this->actingAs($secondUser)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertDontSee('Hello there', false);
    }

    public function test_financial_chat_ui_offers_finance_and_general_suggestions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Ask me anything — finance or otherwise')
            ->assertSee('Where does most of my money go?')
            ->assertSee('Am I staying within my budgets?')
            ->assertSee('Compare my spending this month and last month.')
            ->assertSee('Help me create a savings plan.')
            ->assertSee('Explain compound interest.')
            ->assertSee('Can you help me with a programming question?')
            ->assertSee('SpendWise AI needs to be configured');
    }

    public function test_provider_connection_timeouts_return_a_helpful_error(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('Connection timed out.');
            },
        ]);
        Log::shouldReceive('warning')
            ->once()
            ->with('Gemini report assistant connection failed.', [
                'exception' => ConnectionException::class,
            ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'SpendWise AI could not respond right now. Please try again in a moment.');
    }

    public function test_provider_rate_limits_return_a_helpful_error(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'too many requests'], 429),
        ]);
        Log::shouldReceive('warning')->once()
            ->with('Gemini report assistant returned an unsuccessful response.', ['status' => 429]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello again'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'The Gemini API quota or rate limit was reached. Check API usage and billing for the project linked to GEMINI_API_KEY. A Gemini app subscription does not automatically include Gemini API quota.');
    }
}
