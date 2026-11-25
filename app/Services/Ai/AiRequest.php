<?php

namespace App\Services\Ai;

/**
 * Provider-neutral request. Built once, so every provider receives the same
 * system instructions, history and financial context.
 */
final class AiRequest
{
    /**
     * @param  array<int, array{role: 'user'|'assistant', content: string}>  $messages  History followed by the current user message.
     * @param  array<string, mixed>|null  $financialContext
     * @param  array<int, mixed>  $history  Raw history, for adapters that wrap legacy providers.
     */
    public function __construct(
        public readonly string $system,
        public readonly array $messages,
        public readonly string $question,
        public readonly array $history,
        public readonly ?array $financialContext,
        public readonly ?string $page = null,
        public readonly bool $expectJson = false,
    ) {}
}
