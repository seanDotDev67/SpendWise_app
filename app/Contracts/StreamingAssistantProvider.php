<?php

namespace App\Contracts;

use App\Services\AssistantProviderException;

interface StreamingAssistantProvider extends AssistantProvider
{
    /**
     * Streams the answer; $onToken receives each new piece of visible text.
     * Returns the full, validated answer, or null when it is invalid or ungrounded.
     *
     * @param  array<int, mixed>  $history
     * @param  callable(string): void  $onToken
     *
     * @throws AssistantProviderException
     */
    public function stream(string $question, array $history, ?array $financialContext, callable $onToken, ?string $page = null): ?string;
}
