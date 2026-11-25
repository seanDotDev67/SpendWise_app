<?php

namespace App\Services\Ai;

final class AiResult
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $finishReason = null,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
        public readonly ?int $totalTokens = null,
        /** True when the adapter already ran the answer through AssistantResponseGuard. */
        public readonly bool $validated = false,
    ) {}
}
