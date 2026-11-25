<?php

namespace App\Services\Ai\Adapters;

use App\Services\Ai\AiFailureKind;
use App\Services\Ai\AiProviderFailure;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use Illuminate\Http\Client\Response;

/** Google Gemini generateContent API. */
class GeminiAdapter extends AbstractHttpAdapter
{
    protected function url(): string
    {
        return rtrim((string) $this->config['endpoint'], '/').'/'.rawurlencode((string) $this->model()).':generateContent';
    }

    protected function headers(): array
    {
        return ['x-goog-api-key' => (string) $this->config['api_key']];
    }

    protected function payload(AiRequest $request): array
    {
        $generation = [
            'maxOutputTokens' => (int) ($this->config['max_output_tokens'] ?? 2048),
            'temperature' => (float) ($this->config['temperature'] ?? 0.3),
        ];
        if ($request->expectJson) {
            $generation['responseMimeType'] = 'application/json';
        }

        return [
            'systemInstruction' => ['parts' => [['text' => $request->system]]],
            'contents' => array_map(fn (array $message): array => [
                'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message['content']]],
            ], $request->messages),
            'generationConfig' => $generation,
        ];
    }

    protected function parse(Response $response): AiResult
    {
        if (is_string($response->json('promptFeedback.blockReason'))) {
            throw new AiProviderFailure(AiFailureKind::Blocked);
        }

        $finish = $response->json('candidates.0.finishReason');
        $parts = $response->json('candidates.0.content.parts');
        $text = is_array($parts)
            ? implode("\n", array_map(
                fn (array $part): string => $part['text'],
                array_filter($parts, fn ($part): bool => is_array($part)
                    && ($part['thought'] ?? false) !== true
                    && is_string($part['text'] ?? null))
            ))
            : '';

        if (in_array($finish, ['SAFETY', 'RECITATION', 'PROHIBITED_CONTENT', 'BLOCKLIST'], true)) {
            throw new AiProviderFailure(AiFailureKind::Blocked);
        }

        if (! in_array($finish, ['STOP', 'MAX_TOKENS'], true) || trim($text) === '') {
            throw new AiProviderFailure(AiFailureKind::BadResponse);
        }

        return new AiResult(
            trim($text),
            $finish,
            $this->intOrNull($response->json('usageMetadata.promptTokenCount')),
            $this->intOrNull($response->json('usageMetadata.candidatesTokenCount')),
            $this->intOrNull($response->json('usageMetadata.totalTokenCount')),
        );
    }
}
