<?php

namespace App\Contracts;

use App\Services\Ai\AiProviderFailure;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;

interface StreamingAiProviderAdapter extends AiProviderAdapter
{
    /**
     * @param  callable(string): void  $onToken
     *
     * @throws AiProviderFailure
     */
    public function stream(AiRequest $request, callable $onToken): AiResult;
}
