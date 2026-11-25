<?php

namespace App\Services\Ai\Adapters;

use App\Services\Ai\AiFailureKind;
use App\Services\Ai\AiProviderFailure;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use Illuminate\Http\Client\Response;

/** Base for vendors that expose an OpenAI-style POST /chat/completions API. */
abstract class OpenAiCompatibleAdapter extends AbstractHttpAdapter
{
    protected function url(): string
    {
        return rtrim((string) $this->config['endpoint'], '/').'/chat/completions';
    }

    protected function headers(): array
    {
        return ['Authorization' => 'Bearer '.$this->config['api_key']];
    }

    protected function payload(AiRequest $request): array
    {
        $payload = [
            'model' => $this->model(),
            'messages' => [['role' => 'system', 'content' => $request->system], ...$request->messages],
            'max_tokens' => (int) ($this->config['max_output_tokens'] ?? 1024),
            'temperature' => (float) ($this->config['temperature'] ?? 0.3),
        ];
        if ($request->expectJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        return $payload;
    }

    protected function parse(Response $response): AiResult
    {
        // Some gateways return HTTP 200 with an error object instead of choices.
        if (is_array($response->json('error'))) {
            $code = $response->json('error.code');

            throw new AiProviderFailure(
                is_int($code) ? AiFailureKind::fromHttpStatus($code) : AiFailureKind::BadResponse,
                is_int($code) ? $code : null
            );
        }

        $content = $response->json('choices.0.message.content');
        $finish = $response->json('choices.0.finish_reason');
        $text = is_string($content) ? $this->stripReasoning($content) : '';

        if ($finish === 'content_filter') {
            throw new AiProviderFailure(AiFailureKind::Blocked);
        }

        if ($text === '' || ! in_array($finish, ['stop', 'length', null], true)) {
            throw new AiProviderFailure(AiFailureKind::BadResponse);
        }

        return new AiResult(
            $text,
            is_string($finish) ? $finish : null,
            $this->intOrNull($response->json('usage.prompt_tokens')),
            $this->intOrNull($response->json('usage.completion_tokens')),
            $this->intOrNull($response->json('usage.total_tokens')),
        );
    }
}
