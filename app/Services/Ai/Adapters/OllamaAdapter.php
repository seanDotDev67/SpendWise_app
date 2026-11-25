<?php

namespace App\Services\Ai\Adapters;

use App\Contracts\StreamingAiProviderAdapter;
use App\Services\Ai\AiFailureKind;
use App\Services\Ai\AiProviderFailure;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use App\Services\AssistantProviderException;
use App\Services\OllamaChatProvider;

/**
 * Local development adapter that wraps the existing OllamaChatProvider.
 * Ollama already validates its answers, so the result is marked as validated.
 */
class OllamaAdapter implements StreamingAiProviderAdapter
{
    /** @param  array<string, mixed>  $config */
    public function __construct(
        private readonly string $name,
        private readonly array $config,
        private readonly OllamaChatProvider $ollama,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function model(): ?string
    {
        $model = config('services.ollama.model');

        return is_string($model) && $model !== '' ? $model : null;
    }

    public function isConfigured(): bool
    {
        $enabled = $this->config['enabled'] ?? null;
        $enabled = is_null($enabled) || $enabled === ''
            ? app()->environment('local', 'testing')
            : filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        return $enabled && $this->ollama->isConfigured();
    }

    public function complete(AiRequest $request): AiResult
    {
        try {
            $answer = $this->ollama->respond($request->question, $request->history, $request->financialContext, $request->page);
        } catch (AssistantProviderException $exception) {
            throw $this->failure($exception);
        }

        return $this->result($answer);
    }

    public function stream(AiRequest $request, callable $onToken): AiResult
    {
        try {
            $answer = $this->ollama->stream($request->question, $request->history, $request->financialContext, $onToken, $request->page);
        } catch (AssistantProviderException $exception) {
            throw $this->failure($exception);
        }

        return $this->result($answer);
    }

    private function result(?string $answer): AiResult
    {
        if ($answer === null || trim($answer) === '') {
            throw new AiProviderFailure(AiFailureKind::BadResponse);
        }

        return new AiResult($answer, 'stop', validated: true);
    }

    private function failure(AssistantProviderException $exception): AiProviderFailure
    {
        $timedOut = str_contains($exception->getMessage(), 'took too long');

        return new AiProviderFailure(
            $timedOut ? AiFailureKind::Timeout : AiFailureKind::Connection,
            safeMessage: $exception->getMessage()
        );
    }
}
