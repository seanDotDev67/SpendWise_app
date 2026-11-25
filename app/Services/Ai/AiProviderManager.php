<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderAdapter;
use App\Contracts\StreamingAiProviderAdapter;
use App\Contracts\StreamingAssistantProvider;
use App\Services\AssistantProviderException;
use App\Services\AssistantResponseGuard;
use Illuminate\Support\Str;
use Throwable;

/**
 * Tries the configured providers in priority order and fails over on
 * recoverable errors. Each provider is attempted at most once per request,
 * and the number of attempts is capped, so it can never loop.
 *
 * Only the model call is covered here; no database work is retried.
 */
class AiProviderManager implements StreamingAssistantProvider
{
    private const MAX_HISTORY_MESSAGES = 12;

    private const MAX_HISTORY_MESSAGE_CHARACTERS = 1500;

    public const UNAVAILABLE_MESSAGE = 'SpendWise AI is temporarily unavailable. Please try again in a few minutes.';

    /** @var array<int, AiProviderAdapter>|null */
    private ?array $adapters = null;

    public function __construct(
        private readonly AssistantResponseGuard $guard,
        private readonly ProviderHealthTracker $health,
        private readonly AiCallLogger $logger,
    ) {}

    public function isConfigured(): bool
    {
        return $this->adapters() !== [];
    }

    /** @return array<int, AiProviderAdapter> Configured adapters in priority order. */
    public function adapters(): array
    {
        if ($this->adapters !== null) {
            return $this->adapters;
        }

        $definitions = collect((array) config('ai.providers'))
            ->filter(fn ($definition): bool => is_array($definition) && is_string($definition['adapter'] ?? null))
            ->sortBy(fn (array $definition): int => (int) ($definition['priority'] ?? 100));

        $adapters = [];
        foreach ($definitions as $name => $definition) {
            $adapter = app()->make($definition['adapter'], ['name' => (string) $name, 'config' => $definition]);
            if ($adapter instanceof AiProviderAdapter && $adapter->isConfigured()) {
                $adapters[] = $adapter;
            }
        }

        return $this->adapters = $adapters;
    }

    public function respond(string $question, array $history, ?array $financialContext, ?string $page = null): ?string
    {
        return $this->run($this->buildRequest($question, $history, $financialContext, $page), null);
    }

    public function stream(string $question, array $history, ?array $financialContext, callable $onToken, ?string $page = null): ?string
    {
        return $this->run($this->buildRequest($question, $history, $financialContext, $page), $onToken);
    }

    /**
     * @param  callable(string): void|null  $onToken
     */
    private function run(AiRequest $request, ?callable $onToken): string
    {
        $maxAttempts = max(1, (int) config('ai.max_attempts', 3));
        $attempted = [];
        $skipped = 0;
        $lastFailure = null;
        $emitted = false;
        $tracking = $onToken === null ? null : function (string $piece) use ($onToken, &$emitted): void {
            $emitted = true;
            $onToken($piece);
        };

        foreach ($this->adapters() as $adapter) {
            if (! $this->health->isAvailable($adapter->name())) {
                $skipped++;

                continue;
            }

            if (count($attempted) >= $maxAttempts) {
                break;
            }

            $attempted[] = $adapter->name();
            $started = hrtime(true);

            try {
                $result = $tracking !== null && $adapter instanceof StreamingAiProviderAdapter
                    ? $adapter->stream($request, $tracking)
                    : $adapter->complete($request);

                $text = $this->validate($result, $request);
            } catch (AiProviderFailure $failure) {
                $lastFailure = $failure;
                $this->recordFailure($adapter, $failure, $started, count($attempted));

                if ($emitted) {
                    // Text already reached the client; switching providers would corrupt the answer.
                    break;
                }

                continue;
            } catch (Throwable) {
                $lastFailure = new AiProviderFailure(AiFailureKind::BadResponse);
                $this->recordFailure($adapter, $lastFailure, $started, count($attempted));

                if ($emitted) {
                    break;
                }

                continue;
            }

            $this->health->recordSuccess($adapter->name());
            $this->logger->attempt([
                'provider' => $adapter->name(),
                'model' => $adapter->model(),
                'status' => 'ok',
                'latency_ms' => $this->elapsed($started),
                'attempt' => count($attempted),
                'failover_attempts' => count($attempted) - 1,
                'prompt_tokens' => $result->promptTokens,
                'completion_tokens' => $result->completionTokens,
                'total_tokens' => $result->totalTokens,
            ]);

            if ($tracking !== null && ! $emitted) {
                $tracking($text);
            }

            return $text;
        }

        $this->logger->exhausted($attempted, $skipped);

        // A single attempt may carry a specific, actionable (already user-safe) message.
        throw new AssistantProviderException(
            count($attempted) === 1 && $lastFailure?->safeMessage !== null
                ? $lastFailure->safeMessage
                : self::UNAVAILABLE_MESSAGE
        );
    }

    /** @throws AiProviderFailure */
    private function validate(AiResult $result, AiRequest $request): string
    {
        $text = trim($result->text);
        if (! $result->validated) {
            $text = trim(preg_replace('/\$\s?(?=\d)/', '₱', $text) ?? $text);
        }

        if ($text === '' || Str::length($text) > (int) config('ai.max_answer_characters', 4000)) {
            throw new AiProviderFailure(AiFailureKind::BadResponse);
        }

        if ($result->validated || $request->financialContext === null) {
            return $text;
        }

        if (
            config('ai.reject_unsupported_numbers', true)
            && $this->guard->hasUnsupportedNumericClaims($text, $request->financialContext)
        ) {
            throw new AiProviderFailure(AiFailureKind::BadResponse);
        }

        return $this->guard->guardGoalAnswer($text, $request->financialContext);
    }

    private function recordFailure(AiProviderAdapter $adapter, AiProviderFailure $failure, int $started, int $attempt): void
    {
        $this->health->recordFailure($adapter->name(), $failure->kind, $failure->status, $failure->retryAfter);
        $this->logger->attempt([
            'provider' => $adapter->name(),
            'model' => $adapter->model(),
            'status' => 'failed',
            'latency_ms' => $this->elapsed($started),
            'attempt' => $attempt,
            'failure_kind' => $failure->kind->value,
            'http_status' => $failure->status,
        ]);
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    /**
     * @param  array<int, mixed>  $history
     * @param  array<string, mixed>|null  $financialContext
     */
    private function buildRequest(string $question, array $history, ?array $financialContext, ?string $page, bool $expectJson = false): AiRequest
    {
        $recent = collect($history)
            ->filter(fn ($message): bool => is_array($message)
                && in_array($message['role'] ?? null, ['user', 'assistant'], true)
                && is_string($message['content'] ?? null)
                && trim($message['content']) !== '')
            ->slice(-self::MAX_HISTORY_MESSAGES)
            ->map(fn (array $message): array => [
                'role' => $message['role'],
                'content' => Str::limit(trim($message['content']), self::MAX_HISTORY_MESSAGE_CHARACTERS, '…'),
            ])
            ->values()
            ->all();

        $messages = [...$recent, ['role' => 'user', 'content' => $this->guard->userMessage($question, $financialContext)]];

        return new AiRequest(
            $this->guard->systemPrompt($page),
            $messages,
            $question,
            $history,
            $financialContext,
            $page,
            $expectJson,
        );
    }
}
