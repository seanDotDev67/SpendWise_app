<?php

namespace Tests\Feature;

use App\Contracts\AssistantProvider;
use App\Models\User;
use App\Services\Ai\AiCallLogger;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\ProviderHealthTracker;
use App\Services\AssistantProviderException;
use App\Services\AssistantResponseGuard;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

class AiFailoverTest extends TestCase
{
    use RefreshDatabase;

    private const GEMINI = 'generativelanguage.googleapis.com/*';

    private const GROQ = 'api.groq.com/*';

    private const OPENROUTER = 'openrouter.ai/*';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.assistant.provider' => 'failover',
            'ai.log_channel' => 'ai_capture',
            'logging.channels.ai_capture' => ['driver' => 'monolog', 'handler' => TestHandler::class],
            'ai.providers.ollama.enabled' => false,
            'ai.providers.gemini.api_key' => 'secret-gemini-key',
            'ai.providers.groq.api_key' => 'secret-groq-key',
            'ai.providers.openrouter.api_key' => 'secret-openrouter-key',
        ]);
    }

    private function gemini(string $text = 'Gemini answer.'): Response|PromiseInterface
    {
        return Http::response([
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $text]]]]],
            'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 3, 'totalTokenCount' => 13],
        ]);
    }

    private function openAi(string $text): PromiseInterface
    {
        return Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => $text]]],
            'usage' => ['prompt_tokens' => 9, 'completion_tokens' => 2, 'total_tokens' => 11],
        ]);
    }

    /** @return array<int, array{message: string, context: array}> */
    private function aiLogs(): array
    {
        /** @var TestHandler $handler */
        $handler = Log::channel('ai_capture')->getLogger()->getHandlers()[0];

        return array_map(fn ($record): array => ['message' => $record['message'], 'context' => $record['context']], $handler->getRecords());
    }

    private function ask(string $question = 'How do I add a transaction?'): TestResponse
    {
        return $this->actingAs(User::factory()->create())->postJson(route('reports.assistant'), [
            'question' => $question,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);
    }

    private function sentTo(string $host): int
    {
        return Http::recorded()->filter(fn (array $pair): bool => str_contains($pair[0]->url(), $host))->count();
    }

    public function test_the_primary_provider_answers_without_touching_the_others(): void
    {
        Http::fake([self::GEMINI => $this->gemini(), self::GROQ => $this->openAi('groq'), self::OPENROUTER => $this->openAi('or')]);

        $this->ask()->assertOk()->assertJsonPath('answer', 'Gemini answer.');

        $this->assertSame(0, $this->sentTo('groq.com'));
        $this->assertSame(0, $this->sentTo('openrouter.ai'));
    }

    public function test_a_rate_limited_primary_fails_over_to_groq_and_is_skipped_during_its_cooldown(): void
    {
        Http::fake([
            self::GEMINI => Http::response(['error' => 'quota'], 429),
            self::GROQ => $this->openAi('Groq answer.'),
        ]);

        $this->ask()->assertOk()->assertJsonPath('answer', 'Groq answer.');
        $this->assertFalse(app(ProviderHealthTracker::class)->isAvailable('gemini'));

        $this->ask()->assertOk()->assertJsonPath('answer', 'Groq answer.');
        $this->assertSame(1, $this->sentTo('generativelanguage'), 'Gemini is not called again while cooling down.');
    }

    public function test_the_chain_continues_to_openrouter_after_503_and_a_timeout(): void
    {
        Http::fake([
            self::GEMINI => Http::response('', 503),
            self::GROQ => Http::failedConnection('cURL error 28: Operation timed out'),
            self::OPENROUTER => $this->openAi('OpenRouter answer.'),
        ]);

        $this->ask()->assertOk()->assertJsonPath('answer', 'OpenRouter answer.');

        $logs = $this->aiLogs();
        $this->assertSame(['unavailable', 'timeout', null], array_map(fn ($l) => $l['context']['failure_kind'] ?? null, $logs));
        $this->assertSame(2, $logs[2]['context']['failover_attempts']);
        $this->assertSame('openrouter', $logs[2]['context']['provider']);
        $this->assertSame(11, $logs[2]['context']['total_tokens']);
        $this->assertArrayHasKey('latency_ms', $logs[2]['context']);
    }

    public function test_when_every_provider_fails_a_safe_error_is_returned_and_nothing_is_fabricated(): void
    {
        Http::fake([
            self::GEMINI => Http::response('', 503),
            self::GROQ => Http::response('', 429),
            self::OPENROUTER => Http::response('', 500),
        ]);

        $this->ask()
            ->assertStatus(503)
            ->assertJsonPath('message', AiProviderManager::UNAVAILABLE_MESSAGE)
            ->assertJsonMissingPath('answer');

        $this->assertStringContainsString('All AI providers failed.', collect($this->aiLogs())->last()['message']);
    }

    public function test_authentication_and_invalid_request_errors_fail_over_once_without_retrying_the_provider(): void
    {
        Http::fake([
            self::GEMINI => Http::response(['error' => 'bad key'], 401),
            self::GROQ => Http::response(['error' => 'bad request'], 400),
            self::OPENROUTER => $this->openAi('OpenRouter answer.'),
        ]);

        $this->ask()->assertOk();

        $this->assertSame(1, $this->sentTo('generativelanguage'));
        $this->assertSame(1, $this->sentTo('api.groq.com'));

        $tracker = app(ProviderHealthTracker::class);
        $this->assertFalse($tracker->isAvailable('gemini'));
        $this->assertFalse($tracker->isAvailable('groq'));
    }

    public function test_attempts_are_capped(): void
    {
        config(['ai.max_attempts' => 2]);
        Http::fake([
            self::GEMINI => Http::response('', 503),
            self::GROQ => Http::response('', 503),
            self::OPENROUTER => $this->openAi('never reached'),
        ]);

        $this->ask()->assertStatus(503);

        $this->assertSame(0, $this->sentTo('openrouter.ai'));
    }

    public function test_context_history_and_instructions_are_identical_for_every_provider(): void
    {
        Http::fake([self::GEMINI => Http::response('', 503), self::GROQ => $this->openAi('ok')]);

        $history = [['role' => 'user', 'content' => 'My earlier question'], ['role' => 'assistant', 'content' => 'My earlier answer']];
        $context = ['total_income' => 1000.0];
        app(AssistantProvider::class)->respond('Current question', $history, $context, 'budgets');

        $requests = Http::recorded()->map(fn (array $pair): Request => $pair[0]);
        $gemini = $requests->first(fn (Request $r): bool => str_contains($r->url(), 'generativelanguage'))->data();
        $groq = $requests->first(fn (Request $r): bool => str_contains($r->url(), 'groq'))->data();

        $this->assertSame($gemini['systemInstruction']['parts'][0]['text'], $groq['messages'][0]['content']);
        $this->assertStringContainsString('Budgets page', $groq['messages'][0]['content']);
        $this->assertSame('My earlier question', $groq['messages'][1]['content']);
        $this->assertSame('My earlier answer', $groq['messages'][2]['content']);
        $this->assertSame($gemini['contents'][2]['parts'][0]['text'], $groq['messages'][3]['content']);
        $this->assertStringContainsString('"total_income":1000', $groq['messages'][3]['content']);
    }

    public function test_an_ungrounded_answer_is_rejected_and_the_next_provider_is_tried(): void
    {
        Http::fake([
            self::GEMINI => $this->gemini('You spent ₱98,765.43 last month.'),
            self::GROQ => $this->openAi('You recorded ₱1,000.00 income.'),
        ]);

        $answer = app(AssistantProvider::class)->respond('What is my income?', [], ['income' => 1000.0]);

        $this->assertSame('You recorded ₱1,000.00 income.', $answer);
        $this->assertTrue(app(ProviderHealthTracker::class)->isAvailable('gemini'), 'A bad answer is not an outage.');
    }

    public function test_streaming_fails_over_before_the_first_token_and_delivers_one_chunk(): void
    {
        Http::fake([self::GEMINI => Http::response('', 503), self::GROQ => $this->openAi('Streamed fallback.')]);

        $pieces = [];
        $answer = app(AssistantProvider::class)->stream('Hi', [], null, function (string $piece) use (&$pieces): void {
            $pieces[] = $piece;
        });

        $this->assertSame('Streamed fallback.', $answer);
        $this->assertSame(['Streamed fallback.'], $pieces);
    }

    public function test_monitoring_records_never_contain_keys_or_financial_content(): void
    {
        Http::fake([self::GEMINI => Http::response('', 429), self::GROQ => $this->openAi('Fine.')]);

        app(AssistantProvider::class)->respond('Private question about salary', [], ['total_income' => 54321.0]);

        $dump = json_encode($this->aiLogs());
        foreach (['secret-gemini-key', 'secret-groq-key', 'Private question', '54321'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $dump);
        }
        $this->assertStringContainsString('"provider":"gemini"', $dump);
        $this->assertStringContainsString('"failure_kind":"rate_limited"', $dump);
    }

    public function test_providers_without_keys_are_skipped_and_no_keys_means_not_configured(): void
    {
        config(['ai.providers.gemini.api_key' => null, 'ai.providers.openrouter.api_key' => null]);
        Http::fake([self::GROQ => $this->openAi('Groq only.')]);

        $this->ask()->assertOk()->assertJsonPath('answer', 'Groq only.');
        $this->assertSame(0, $this->sentTo('generativelanguage'));

        config(['ai.providers.groq.api_key' => null]);
        $this->assertFalse((new AiProviderManager(
            app(AssistantResponseGuard::class), app(ProviderHealthTracker::class), app(AiCallLogger::class)
        ))->isConfigured());
        $this->ask()->assertStatus(503);
    }

    public function test_priority_order_is_configurable(): void
    {
        config(['ai.providers.groq.priority' => 0]);
        Http::fake([self::GROQ => $this->openAi('Groq first.'), self::GEMINI => $this->gemini()]);

        $this->ask()->assertOk()->assertJsonPath('answer', 'Groq first.');
        $this->assertSame(0, $this->sentTo('generativelanguage'));
    }

    public function test_a_single_failing_provider_throws_a_provider_exception(): void
    {
        config(['ai.providers.groq.api_key' => null, 'ai.providers.openrouter.api_key' => null]);
        Http::fake([self::GEMINI => Http::response('', 503)]);

        $this->expectException(AssistantProviderException::class);

        app(AssistantProvider::class)->respond('Hi', [], null);
    }
}
