<?php

namespace Tests\Feature;

use App\Contracts\AssistantProvider;
use App\Models\User;
use App\Services\GeminiReportResponder;
use App\Services\OllamaChatProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OllamaAssistantTest extends TestCase
{
    use RefreshDatabase;

    private const FRIENDLY_TIMEOUT = 'SpendWise AI’s local model took too long to respond. Please try again, or ask the administrator to use a smaller model.';

    private const FRIENDLY_DOWN = 'SpendWise AI’s local model is not running. Please ask the administrator to start Ollama and try again.';

    private const FRIENDLY_MISSING = 'SpendWise AI’s local model is not installed yet. Please ask the administrator to download the configured Ollama model.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.assistant.provider' => 'ollama',
            'services.ollama.base_url' => 'http://localhost:11434',
            'services.ollama.model' => 'llama3.2:3b',
            'services.gemini.api_key' => null,
        ]);
    }

    private function fakeOllama(string $answer = 'Here is a helpful answer.', array $chatOverride = []): void
    {
        Http::fake([
            'localhost:11434/api/tags' => Http::response([
                'models' => [['name' => 'llama3.2:3b', 'model' => 'llama3.2:3b']],
            ]),
            'localhost:11434/api/chat' => Http::response($chatOverride ?: [
                'model' => 'llama3.2:3b',
                'message' => ['role' => 'assistant', 'content' => $answer],
                'done' => true,
                'done_reason' => 'stop',
            ]),
        ]);
    }

    private function chatRequests(): array
    {
        return Http::recorded()
            ->map(fn (array $pair): Request => $pair[0])
            ->filter(fn (Request $request): bool => str_ends_with($request->url(), '/api/chat'))
            ->values()
            ->all();
    }

    public function test_ollama_is_the_default_provider_and_gemini_is_not_required(): void
    {
        $this->assertSame('ollama', config('services.assistant.provider'));
        $this->assertInstanceOf(OllamaChatProvider::class, app(AssistantProvider::class));

        config(['services.assistant.provider' => 'gemini']);
        $this->assertInstanceOf(GeminiReportResponder::class, app(AssistantProvider::class));
    }

    public function test_successful_ollama_response_uses_the_documented_chat_request_shape(): void
    {
        $this->fakeOllama('Laravel MVC separates data, requests, and presentation.');

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Explain PHP MVC.'])
            ->assertOk()
            ->assertExactJson(['answer' => 'Laravel MVC separates data, requests, and presentation.']);

        [$request] = $this->chatRequests();
        $body = $request->data();
        $this->assertSame('http://localhost:11434/api/chat', $request->url());
        $this->assertSame('llama3.2:3b', $body['model']);
        $this->assertFalse($body['stream']);
        $this->assertSame('system', $body['messages'][0]['role']);
        $this->assertStringContainsString('SpendWise AI', $body['messages'][0]['content']);
        $this->assertSame(['role' => 'user', 'content' => 'Explain PHP MVC.'], $body['messages'][1]);
        $this->assertFalse($request->hasHeader('x-goog-api-key'));
        $this->assertFalse($request->hasHeader('Authorization'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'generativelanguage'));
    }

    public function test_general_questions_do_not_retrieve_or_send_financial_data(): void
    {
        $this->fakeOllama('A JOIN combines rows from related tables.');
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
            ->postJson(route('reports.assistant'), ['question' => 'Help me understand SQL joins.'])
            ->assertOk();

        [$request] = $this->chatRequests();
        $payload = json_encode($request->data());
        $this->assertStringNotContainsString('Verified financial context', $payload);
        $this->assertStringNotContainsString('999', $payload);
        $this->assertStringNotContainsString('Private', $payload);
    }

    public function test_financial_questions_send_only_the_authenticated_users_aggregates(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $this->fakeOllama('Food is your largest recorded expense category.');
        $user = User::factory()->create(['name' => 'Private Account Name']);
        $other = User::factory()->create();
        $food = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $otherCategory = $other->categories()->create(['name' => 'Other user category', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $food->id,
            'amount' => 80,
            'type' => 'expense',
            'description' => 'Private transaction description',
            'date' => '2026-10-12',
        ]);
        $other->transactions()->create([
            'category_id' => $otherCategory->id,
            'amount' => 9999,
            'type' => 'expense',
            'description' => 'Other user private transaction',
            'date' => '2026-10-12',
        ]);

        $this->actingAs($user)
            ->postJson(route('reports.assistant'), [
                'question' => 'Where does most of my money go?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'user_id' => $other->id,
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Food is your largest recorded expense category.');

        [$request] = $this->chatRequests();
        $payload = json_encode($request->data());
        $this->assertStringContainsString('Verified financial context', $payload);
        $this->assertStringContainsString('80.00', $payload);
        $this->assertStringContainsString('Food', $payload);
        $this->assertStringNotContainsString('9999', $payload);
        $this->assertStringNotContainsString('Other user category', $payload);
        $this->assertStringNotContainsString('Private transaction description', $payload);
        $this->assertStringNotContainsString('Private Account Name', $payload);
    }

    public function test_ungrounded_financial_numbers_from_the_local_model_are_rejected(): void
    {
        $this->fakeOllama('You spent ₱12,345 on food.');
        Log::shouldReceive('warning')
            ->once()
            ->with('Ollama returned an invalid or ungrounded response.', [
                'done' => true,
                'done_reason' => 'stop',
                'has_text' => true,
                'too_long' => false,
                'unsupported_financial_number' => true,
            ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'How much did I spend this month?'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'SpendWise AI could not respond right now. Please try again in a moment.');
    }

    public function test_history_is_sent_for_follow_ups_and_topic_switches_drop_financial_context(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        Http::fake([
            'localhost:11434/api/tags' => Http::response(['models' => [['name' => 'llama3.2:3b']]]),
            'localhost:11434/api/chat' => Http::sequence()
                ->push(['message' => ['role' => 'assistant', 'content' => 'Food is your top category.'], 'done' => true])
                ->push(['message' => ['role' => 'assistant', 'content' => 'PHP MVC splits an app into three parts.'], 'done' => true])
                ->push(['message' => ['role' => 'assistant', 'content' => 'A controller coordinates requests.'], 'done' => true]),
        ]);
        $user = User::factory()->create();
        $food = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $food->id,
            'amount' => 120,
            'type' => 'expense',
            'date' => '2026-10-12',
        ]);

        $this->actingAs($user);
        $this->postJson(route('reports.assistant'), ['question' => 'Where does most of my money go?'])->assertOk();
        $this->postJson(route('reports.assistant'), ['question' => 'Explain PHP MVC.'])->assertOk();
        $this->postJson(route('reports.assistant'), ['question' => 'Tell me more'])->assertOk();

        [$first, $second, $third] = $this->chatRequests();
        $secondMessages = $second->data()['messages'];
        $this->assertSame('system', $secondMessages[0]['role']);
        $this->assertSame('user', $secondMessages[1]['role']);
        $this->assertSame('Where does most of my money go?', $secondMessages[1]['content']);
        $this->assertSame(['role' => 'assistant', 'content' => 'Food is your top category.'], $secondMessages[2]);
        $this->assertStringNotContainsString('Verified financial context', json_encode($second->data()));

        $thirdPayload = json_encode($third->data());
        $this->assertStringContainsString('PHP MVC splits an app into three parts.', $thirdPayload);
        $thirdMessages = $third->data()['messages'];
        $this->assertStringNotContainsString('Verified financial context', $thirdMessages[count($thirdMessages) - 1]['content']);
    }

    public function test_only_recent_bounded_history_is_sent_to_the_model(): void
    {
        $this->fakeOllama();
        $history = [];
        foreach (range(1, 20) as $i) {
            $history[] = ['role' => $i % 2 ? 'user' : 'assistant', 'content' => str_repeat('x', 3000).$i];
        }

        $provider = app(OllamaChatProvider::class);
        $provider->respond('Hello', $history, null);

        [$request] = $this->chatRequests();
        $messages = $request->data()['messages'];
        $this->assertCount(14, $messages);
        foreach (array_slice($messages, 1, 12) as $message) {
            $this->assertLessThanOrEqual(1501, mb_strlen($message['content']));
        }
    }

    public function test_connection_failure_returns_a_friendly_error_and_logs_diagnostics_without_internals(): void
    {
        Http::fake(['localhost:11434/*' => function () {
            throw new ConnectionException('cURL error 7: Failed to connect to localhost port 11434');
        }]);
        Log::shouldReceive('warning')
            ->once()
            ->with('Ollama connection failed.', [
                'operation' => 'tags',
                'exception' => ConnectionException::class,
                'timed_out' => false,
            ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::FRIENDLY_DOWN]);
    }

    public function test_timeout_returns_a_friendly_error(): void
    {
        Http::fake([
            'localhost:11434/api/tags' => Http::response(['models' => [['name' => 'llama3.2:3b']]]),
            'localhost:11434/api/chat' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 120000 milliseconds');
            },
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::FRIENDLY_TIMEOUT]);
    }

    public function test_missing_model_is_detected_before_inference(): void
    {
        Http::fake([
            'localhost:11434/api/tags' => Http::response(['models' => [['name' => 'mistral:latest']]]),
            'localhost:11434/api/chat' => Http::response([]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::FRIENDLY_MISSING]);

        $this->assertCount(0, $this->chatRequests());
    }

    public function test_chat_404_for_a_missing_model_is_handled_when_the_model_list_is_unavailable(): void
    {
        Http::fake([
            'localhost:11434/api/tags' => Http::response('nope', 500),
            'localhost:11434/api/chat' => Http::response(['error' => 'model "llama3.2:3b" not found, try pulling it first'], 404),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::FRIENDLY_MISSING]);
    }

    public function test_untagged_model_names_match_the_latest_tag(): void
    {
        config(['services.ollama.model' => 'llama3.2']);
        Http::fake([
            'localhost:11434/api/tags' => Http::response(['models' => [['name' => 'llama3.2:latest']]]),
            'localhost:11434/api/chat' => Http::response(['message' => ['role' => 'assistant', 'content' => 'Hi!'], 'done' => true]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertOk()
            ->assertJsonPath('answer', 'Hi!');
    }

    public function test_malformed_and_empty_model_responses_return_a_generic_error_without_internals(): void
    {
        foreach ([
            ['message' => ['role' => 'assistant', 'content' => '']],
            ['unexpected' => 'shape'],
            ['message' => ['content' => '<think>only reasoning</think>'], 'done' => true],
            ['message' => ['content' => ['not', 'a', 'string']], 'done' => true],
        ] as $payload) {
            $this->fakeOllama(chatOverride: $payload);

            $this->actingAs(User::factory()->create())
                ->postJson(route('reports.assistant'), ['question' => 'Hello'])
                ->assertServiceUnavailable()
                ->assertExactJson(['message' => 'SpendWise AI could not respond right now. Please try again in a moment.']);
        }
    }

    public function test_inline_reasoning_is_removed_from_the_answer(): void
    {
        $this->fakeOllama("<think>secret chain of thought</think>\nA model holds data.");

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'What is a model?'])
            ->assertOk()
            ->assertExactJson(['answer' => 'A model holds data.']);
    }

    public function test_invalid_ollama_configuration_blocks_requests_without_calling_any_provider(): void
    {
        config(['services.ollama.base_url' => 'not a url']);
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'SpendWise AI is not configured correctly yet. Please ask the administrator to check the AI provider settings.');

        Http::assertNothingSent();
    }

    public function test_unavailable_ollama_never_falls_back_to_gemini(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake([
            'localhost:11434/*' => function () {
                throw new ConnectionException('refused');
            },
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => []]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => 'Hello'])
            ->assertServiceUnavailable();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'generativelanguage'));
    }

    public function test_history_is_isolated_between_users_and_the_ui_still_renders_it(): void
    {
        $this->fakeOllama('I can help with that.');
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)
            ->postJson(route('reports.assistant'), ['question' => 'Hello there'])
            ->assertOk();
        $this->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Hello there', false)
            ->assertSee('I can help with that.', false)
            ->assertDontSee('SpendWise AI needs to be configured');

        $this->actingAs($second)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertDontSee('Hello there', false);
    }

    public function test_assistant_requires_authentication_and_validates_input(): void
    {
        Http::fake();

        $this->postJson(route('reports.assistant'), ['question' => 'Hello'])->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant'), ['question' => str_repeat('x', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question');

        Http::assertNothingSent();
    }

    /**
     * @param  array<int, string>  $pieces
     */
    private function fakeOllamaStream(array $pieces, bool $done = true): void
    {
        $lines = array_map(fn (string $piece): string => json_encode([
            'model' => 'llama3.2:3b',
            'message' => ['role' => 'assistant', 'content' => $piece],
            'done' => false,
        ]), $pieces);
        if ($done) {
            $lines[] = json_encode(['model' => 'llama3.2:3b', 'message' => ['role' => 'assistant', 'content' => ''], 'done' => true, 'done_reason' => 'stop']);
        }

        Http::fake([
            'localhost:11434/api/tags' => Http::response(['models' => [['name' => 'llama3.2:3b', 'model' => 'llama3.2:3b']]]),
            'localhost:11434/api/chat' => Http::response(implode("\n", $lines)."\n"),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function streamEvents(TestResponse $response): array
    {
        return array_map(
            fn (string $line): array => json_decode($line, true),
            array_values(array_filter(explode("\n", $response->streamedContent())))
        );
    }

    public function test_streaming_sends_tokens_as_they_arrive_and_saves_history(): void
    {
        $this->fakeOllamaStream(['A PHP ', 'interface ', 'is a contract.']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reports.assistant.stream'), ['question' => 'What is a PHP interface?'], ['Accept' => 'application/x-ndjson'])
            ->assertOk();

        $events = $this->streamEvents($response);
        $this->assertSame(['token', 'token', 'token', 'done'], array_column($events, 'type'));
        $this->assertSame('A PHP ', $events[0]['text']);
        $this->assertSame('A PHP interface is a contract.', $events[3]['answer']);

        [$request] = $this->chatRequests();
        $this->assertTrue($request->data()['stream']);
        $this->assertStringNotContainsString('Verified financial context', json_encode($request->data()));

        $this->assertSame(
            [
                ['role' => 'user', 'content' => 'What is a PHP interface?'],
                ['role' => 'assistant', 'content' => 'A PHP interface is a contract.'],
            ],
            session('spendwise_ai_history_'.$user->id)
        );
    }

    public function test_streaming_hides_inline_reasoning(): void
    {
        $this->fakeOllamaStream(['<th', 'ink>secret plan</think>Visible ', 'answer.']);

        $events = $this->streamEvents(
            $this->actingAs(User::factory()->create())->post(route('reports.assistant.stream'), ['question' => 'Hello there friend'])
        );

        $this->assertStringNotContainsString('secret', json_encode($events));
        $this->assertSame('Visible answer.', end($events)['answer']);
    }

    public function test_streaming_rejects_ungrounded_financial_numbers_after_the_stream_ends(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $this->fakeOllamaStream(['You spent ₱', '77,777.00 on food.']);
        $user = User::factory()->create();
        $food = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);
        $user->transactions()->create(['category_id' => $food->id, 'amount' => 80, 'type' => 'expense', 'date' => '2026-10-12']);
        Log::shouldReceive('warning')->once();

        $events = $this->streamEvents(
            $this->actingAs($user)->post(route('reports.assistant.stream'), [
                'question' => 'How much did I spend on food?',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ])
        );

        $this->assertSame('error', end($events)['type']);
        $this->assertNull(session('spendwise_ai_history_'.$user->id));
    }

    public function test_streaming_reports_a_friendly_error_when_ollama_is_down(): void
    {
        Http::fake(['localhost:11434/*' => fn () => throw new ConnectionException('Connection refused')]);
        Log::shouldReceive('warning')->once();

        $events = $this->streamEvents(
            $this->actingAs(User::factory()->create())->post(route('reports.assistant.stream'), ['question' => 'Hello there friend'])
        );

        $this->assertSame([['type' => 'error', 'message' => self::FRIENDLY_DOWN]], $events);
    }

    public function test_an_incomplete_stream_is_not_accepted(): void
    {
        $this->fakeOllamaStream(['Half an answer'], done: false);
        Log::shouldReceive('warning')->once();

        $events = $this->streamEvents(
            $this->actingAs(User::factory()->create())->post(route('reports.assistant.stream'), ['question' => 'Hello there friend'])
        );

        $this->assertSame('error', end($events)['type']);
    }

    public function test_stream_endpoint_requires_authentication_and_validates_input(): void
    {
        Http::fake();

        $this->postJson(route('reports.assistant.stream'), ['question' => 'Hello'])->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->postJson(route('reports.assistant.stream'), [])
            ->assertUnprocessable();

        Http::assertNothingSent();
    }
}
