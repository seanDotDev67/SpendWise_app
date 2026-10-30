<?php

namespace App\Contracts;

use App\Services\AssistantProviderException;

interface AssistantProvider
{
    /**
     * Whether the provider's configuration (not its availability) is usable.
     */
    public function isConfigured(): bool;

    /**
     * @param  array<int, array{role: 'user'|'assistant', content: string}>  $history
     * @param  array<string, mixed>|null  $financialContext  Server-calculated aggregates, or null for general questions.
     * @param  string|null  $page  A server-validated page identifier (see AssistantResponseGuard::PAGES), never free text.
     * @return string|null The validated answer, or null when no usable answer was produced.
     *
     * @throws AssistantProviderException When the failure has a safe, actionable message for the user.
     */
    public function respond(string $question, array $history, ?array $financialContext, ?string $page = null): ?string;
}
