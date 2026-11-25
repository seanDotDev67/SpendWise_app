<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Structured monitoring records. Only whitelisted, non-sensitive fields are
 * written: never prompts, answers, financial data, headers or API keys.
 */
class AiCallLogger
{
    /**
     * @param  array{provider: string, model: ?string, status: 'ok'|'failed', latency_ms: int, attempt: int, failure_kind?: ?string, http_status?: ?int, prompt_tokens?: ?int, completion_tokens?: ?int, total_tokens?: ?int, failover_attempts?: int}  $record
     */
    public function attempt(array $record): void
    {
        $channel = $this->channel();

        $record['status'] === 'ok'
            ? $channel->info('AI provider call succeeded.', $record)
            : $channel->warning('AI provider call failed.', $record);
    }

    /** @param  array<int, string>  $attempted  Provider names, in order. */
    public function exhausted(array $attempted, int $skipped): void
    {
        $this->channel()->error('All AI providers failed.', [
            'attempted' => $attempted,
            'failover_attempts' => max(0, count($attempted) - 1),
            'skipped_unavailable' => $skipped,
        ]);
    }

    private function channel(): LoggerInterface
    {
        $name = config('ai.log_channel');

        return is_string($name) && $name !== '' ? Log::channel($name) : Log::driver();
    }
}
