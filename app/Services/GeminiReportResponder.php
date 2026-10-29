<?php

namespace App\Services;

use App\Contracts\AssistantProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Optional alternative provider. Used only when ASSISTANT_PROVIDER=gemini.
 */
class GeminiReportResponder implements AssistantProvider
{
    public function __construct(private readonly AssistantResponseGuard $guard) {}

    public function isConfigured(): bool
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model');
        $endpoint = config('services.gemini.endpoint');

        return is_string($apiKey)
            && trim($apiKey) !== ''
            && is_string($model)
            && trim($model) !== ''
            && is_string($endpoint)
            && filter_var($endpoint, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($endpoint, PHP_URL_SCHEME)) === 'https';
    }

    /**
     * @param  array<int, array{role: 'user'|'assistant', content: string}>  $history
     */
    public function respond(string $question, array $history, ?array $financialContext, ?string $page = null): ?string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model');
        $endpoint = config('services.gemini.endpoint');
        if (
            ! is_string($apiKey)
            || trim($apiKey) === ''
            || ! is_string($model)
            || trim($model) === ''
            || ! is_string($endpoint)
            || filter_var($endpoint, FILTER_VALIDATE_URL) === false
            || strtolower((string) parse_url($endpoint, PHP_URL_SCHEME)) !== 'https'
        ) {
            return null;
        }

        $contents = array_map(fn (array $message): array => [
            'role' => $message['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $message['content']]],
        ], $history);

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $this->guard->userMessage($question, $financialContext)]],
        ];

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->connectTimeout(5)
                ->timeout(20)
                ->post(
                    rtrim($endpoint, '/').'/'.rawurlencode($model).':generateContent',
                    [
                        'systemInstruction' => [
                            'parts' => [[
                                'text' => $this->guard->systemPrompt($page),
                            ]],
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'thinkingConfig' => [
                                'thinkingLevel' => 'medium',
                            ],
                            'maxOutputTokens' => 2048,
                        ],
                    ]
                );
        } catch (ConnectionException $exception) {
            Log::warning('Gemini report assistant connection failed.', [
                'exception' => $exception::class,
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Gemini report assistant returned an unsuccessful response.', [
                'status' => $response->status(),
            ]);

            if ($response->status() === 429) {
                throw new AssistantProviderException(
                    'The Gemini API quota or rate limit was reached. Check API usage and billing for the project linked to GEMINI_API_KEY. A Gemini app subscription does not automatically include Gemini API quota.'
                );
            }

            return null;
        }

        $candidate = $response->json('candidates.0');
        $parts = $response->json('candidates.0.content.parts');
        $answerParts = is_array($parts)
            ? array_values(array_filter(
                $parts,
                fn ($part): bool => is_array($part)
                    && ($part['thought'] ?? false) !== true
                    && is_string($part['text'] ?? null)
            ))
            : [];
        $answer = implode("\n", array_map(
            fn (array $part): string => $part['text'],
            $answerParts
        ));
        $finishReason = is_array($candidate) ? ($candidate['finishReason'] ?? null) : null;

        if (
            ! is_array($candidate)
            || ! in_array($finishReason, ['STOP', 'MAX_TOKENS'], true)
            || ! is_string($answer)
            || trim($answer) === ''
            || Str::length($answer) > 4000
            || ($financialContext !== null && $this->guard->hasUnsupportedNumericClaims($answer, $financialContext))
        ) {
            Log::warning('Gemini report assistant returned an invalid or ungrounded response.', [
                'finish_reason' => $finishReason,
                'has_text' => is_string($answer) && trim($answer) !== '',
                'too_long' => is_string($answer) && Str::length($answer) > 4000,
                'unsupported_financial_number' => is_string($answer)
                    && $financialContext !== null
                    && $this->guard->hasUnsupportedNumericClaims($answer, $financialContext),
            ]);

            return null;
        }

        return trim($answer);
    }
}
