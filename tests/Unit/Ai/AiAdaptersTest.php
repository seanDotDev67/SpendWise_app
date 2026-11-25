<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\Adapters\GeminiAdapter;
use App\Services\Ai\Adapters\GroqAdapter;
use App\Services\Ai\Adapters\OpenRouterAdapter;
use App\Services\Ai\AiFailureKind;
use App\Services\Ai\AiProviderFailure;
use App\Services\Ai\AiRequest;
use App\Services\Ai\ProviderHealthTracker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAdaptersTest extends TestCase
{
    private function request(bool $json = false): AiRequest
    {
        return new AiRequest(
            'SYSTEM RULES',
            [
                ['role' => 'user', 'content' => 'earlier question'],
                ['role' => 'assistant', 'content' => 'earlier answer'],
                ['role' => 'user', 'content' => 'current question'],
            ],
            'current question',
            [],
            null,
            null,
            $json,
        );
    }

    private function gemini(array $override = []): GeminiAdapter
    {
        return new GeminiAdapter('gemini', $override + [
            'api_key' => 'g-secret',
            'model' => 'gemini-test',
            'endpoint' => 'https://gemini.example.test/v1beta/models',
            'max_output_tokens' => 512,
        ]);
    }

    private function groq(): GroqAdapter
    {
        return new GroqAdapter('groq', [
            'api_key' => 'q-secret',
            'model' => 'llama-test',
            'endpoint' => 'https://groq.example.test/openai/v1',
            'max_output_tokens' => 256,
        ]);
    }

    public function test_gemini_maps_the_neutral_request_and_response(): void
    {
        Http::fake(['gemini.example.test/*' => Http::response([
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [
                ['text' => 'hidden reasoning', 'thought' => true],
                ['text' => 'The answer.'],
            ]]]],
            'usageMetadata' => ['promptTokenCount' => 11, 'candidatesTokenCount' => 5, 'totalTokenCount' => 16],
        ])]);

        $result = $this->gemini()->complete($this->request(true));

        $this->assertSame('The answer.', $result->text);
        $this->assertSame([11, 5, 16], [$result->promptTokens, $result->completionTokens, $result->totalTokens]);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === 'https://gemini.example.test/v1beta/models/gemini-test:generateContent'
                && $request->hasHeader('x-goog-api-key', 'g-secret')
                && $body['systemInstruction']['parts'][0]['text'] === 'SYSTEM RULES'
                && array_column($body['contents'], 'role') === ['user', 'model', 'user']
                && $body['generationConfig']['responseMimeType'] === 'application/json'
                && $body['generationConfig']['maxOutputTokens'] === 512;
        });
    }

    public function test_gemini_safety_blocks_and_empty_candidates_are_classified(): void
    {
        Http::fake(['gemini.example.test/*' => Http::sequence()
            ->push(['candidates' => [['finishReason' => 'SAFETY']]])
            ->push(['candidates' => []])]);

        foreach ([AiFailureKind::Blocked, AiFailureKind::BadResponse] as $expected) {
            try {
                $this->gemini()->complete($this->request());
                $this->fail('Expected a failure.');
            } catch (AiProviderFailure $failure) {
                $this->assertSame($expected, $failure->kind);
            }
        }
    }

    public function test_openai_compatible_adapters_map_request_and_response(): void
    {
        Http::fake(['groq.example.test/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '<think>plan</think>Final answer.']]],
            'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 4, 'total_tokens' => 24],
        ])]);

        $result = $this->groq()->complete($this->request(true));

        $this->assertSame('Final answer.', $result->text);
        $this->assertSame(24, $result->totalTokens);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === 'https://groq.example.test/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer q-secret')
                && $body['messages'][0] === ['role' => 'system', 'content' => 'SYSTEM RULES']
                && count($body['messages']) === 4
                && $body['response_format'] === ['type' => 'json_object']
                && $body['model'] === 'llama-test';
        });
    }

    public function test_openrouter_sends_attribution_headers_and_refuses_paid_models_by_default(): void
    {
        $config = [
            'api_key' => 'o-secret',
            'model' => 'vendor/model:free',
            'endpoint' => 'https://openrouter.example.test/api/v1',
            'referer' => 'https://spendwise.example.test',
            'title' => 'SpendWise',
        ];
        Http::fake(['openrouter.example.test/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'ok']]],
        ])]);

        (new OpenRouterAdapter('openrouter', $config))->complete($this->request());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('HTTP-Referer', 'https://spendwise.example.test')
            && $request->hasHeader('X-Title', 'SpendWise'));

        $paid = ['model' => 'vendor/paid-model'] + $config;
        $this->assertFalse((new OpenRouterAdapter('openrouter', $paid))->isConfigured());
        $this->assertTrue((new OpenRouterAdapter('openrouter', $paid + ['allow_paid_models' => true]))->isConfigured());
    }

    public function test_adapters_require_a_key_and_an_https_endpoint(): void
    {
        $this->assertTrue($this->gemini()->isConfigured());
        $this->assertFalse($this->gemini(['api_key' => ''])->isConfigured());
        $this->assertFalse($this->gemini(['endpoint' => 'http://gemini.example.test'])->isConfigured());
        $this->assertFalse($this->gemini(['enabled' => false])->isConfigured());
    }

    public function test_http_errors_are_classified_and_retry_after_is_read(): void
    {
        $cases = [
            [429, AiFailureKind::RateLimited], [503, AiFailureKind::Unavailable], [500, AiFailureKind::Unavailable],
            [401, AiFailureKind::Auth], [403, AiFailureKind::Auth], [400, AiFailureKind::InvalidRequest],
            [404, AiFailureKind::InvalidRequest], [422, AiFailureKind::InvalidRequest], [408, AiFailureKind::Timeout],
        ];

        $sequence = Http::sequence();
        foreach ($cases as [$status]) {
            $sequence->push(['error' => 'x'], $status, ['Retry-After' => '42']);
        }
        Http::fake(['groq.example.test/*' => $sequence]);

        foreach ($cases as [$status, $kind]) {
            try {
                $this->groq()->complete($this->request());
                $this->fail('Expected a failure.');
            } catch (AiProviderFailure $failure) {
                $this->assertSame($kind, $failure->kind, "HTTP {$status}");
                $this->assertSame($status, $failure->status);
                $this->assertSame(42, $failure->retryAfter);
            }
        }
    }

    public function test_connection_errors_and_timeouts_are_classified(): void
    {
        Http::fake(['groq.example.test/*' => Http::sequence()
            ->pushFailedConnection('cURL error 28: Operation timed out after 20000 milliseconds')
            ->pushFailedConnection('cURL error 6: Could not resolve host')]);

        foreach ([AiFailureKind::Timeout, AiFailureKind::Connection] as $expected) {
            try {
                $this->groq()->complete($this->request());
                $this->fail('Expected a failure.');
            } catch (AiProviderFailure $failure) {
                $this->assertSame($expected, $failure->kind);
            }
        }
    }

    public function test_health_tracker_applies_cooldowns_retry_after_and_recovery(): void
    {
        config(['ai.cooldowns.unavailable' => 30, 'ai.cooldowns.bad_response' => 0]);
        $tracker = new ProviderHealthTracker;

        $this->assertTrue($tracker->isAvailable('gemini'));

        $tracker->recordFailure('gemini', AiFailureKind::BadResponse);
        $this->assertTrue($tracker->isAvailable('gemini'), 'No cooldown for a bad answer.');

        $tracker->recordFailure('gemini', AiFailureKind::Unavailable, 503);
        $this->assertFalse($tracker->isAvailable('gemini'));
        $this->assertSame(2, $tracker->snapshot('gemini')['consecutive_failures']);

        $this->travel(31)->seconds();
        $this->assertTrue($tracker->isAvailable('gemini'));

        $tracker->recordFailure('gemini', AiFailureKind::RateLimited, 429, 120);
        $this->travel(90)->seconds();
        $this->assertFalse($tracker->isAvailable('gemini'), 'Retry-After is honoured.');

        $tracker->recordSuccess('gemini');
        $this->assertTrue($tracker->isAvailable('gemini'));
        $this->assertSame(0, $tracker->snapshot('gemini')['consecutive_failures']);
    }

    public function test_connection_exception_class_is_not_leaked_into_the_failure_message(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7 for https://x/?key=q-secret'));

        try {
            $this->groq()->complete($this->request());
            $this->fail('Expected a failure.');
        } catch (AiProviderFailure $failure) {
            $this->assertStringNotContainsString('q-secret', $failure->getMessage());
        }
    }
}
