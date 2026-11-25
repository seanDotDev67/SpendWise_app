<?php

namespace App\Contracts;

use App\Services\Ai\AiProviderFailure;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;

interface AiProviderAdapter
{
    /** Stable identifier used in config, logs and health state. */
    public function name(): string;

    public function model(): ?string;

    /** Whether the adapter may be used at all (enabled, key present, safe settings). */
    public function isConfigured(): bool;

    /**
     * Performs exactly one attempt; never retries.
     *
     * @throws AiProviderFailure
     */
    public function complete(AiRequest $request): AiResult;
}
