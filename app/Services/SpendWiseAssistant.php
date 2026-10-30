<?php

namespace App\Services;

use App\Contracts\AssistantProvider;
use App\Contracts\StreamingAssistantProvider;
use App\Models\User;
use Carbon\Carbon;

class SpendWiseAssistant
{
    public const HISTORY_KEY_PREFIX = 'spendwise_ai_history_';

    public function __construct(
        private readonly AssistantProvider $provider,
        private readonly AssistantFinancialContext $financialContext
    ) {}

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    public function respond(
        string $question,
        array $history,
        User $user,
        Carbon $startDate,
        Carbon $endDate,
        ?string $page = null
    ): ?string {
        $context = $this->financialContext->forQuestion(
            $user,
            $question,
            $history,
            $startDate,
            $endDate
        );

        return $this->provider->respond($question, $history, $context, $page);
    }

    /**
     * @param  callable(string): void  $onToken
     */
    public function stream(
        string $question,
        array $history,
        User $user,
        Carbon $startDate,
        Carbon $endDate,
        callable $onToken,
        ?string $page = null,
        ?array &$usedContext = null
    ): ?string {
        $usedContext = $context = $this->financialContext->forQuestion(
            $user,
            $question,
            $history,
            $startDate,
            $endDate
        );

        if ($this->provider instanceof StreamingAssistantProvider) {
            return $this->provider->stream($question, $history, $context, $onToken, $page);
        }

        // Providers without streaming deliver the whole answer as one chunk.
        $answer = $this->provider->respond($question, $history, $context, $page);
        if ($answer !== null) {
            $onToken($answer);
        }

        return $answer;
    }
}
