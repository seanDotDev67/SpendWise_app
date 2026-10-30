<?php

namespace App\Services;

use App\Contracts\StreamingAssistantProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Talks to a locally running Ollama server (POST /api/chat, GET /api/tags).
 */
class OllamaChatProvider implements StreamingAssistantProvider
{
    private const MAX_HISTORY_MESSAGES = 12;

    private const MAX_HISTORY_MESSAGE_CHARACTERS = 1500;

    public function __construct(private readonly AssistantResponseGuard $guard) {}

    public function isConfigured(): bool
    {
        return $this->baseUrl() !== null && $this->model() !== null;
    }

    public function respond(string $question, array $history, ?array $financialContext, ?string $page = null): ?string
    {
        $baseUrl = $this->baseUrl();
        $model = $this->model();
        if ($baseUrl === null || $model === null) {
            return null;
        }

        $this->ensureModelIsInstalled($baseUrl, $model);

        try {
            $response = $this->client()
                ->timeout($this->prepareTimeout())
                ->post($baseUrl.'/api/chat', $this->payload($model, $question, $history, $financialContext, false, $page));
        } catch (ConnectionException $exception) {
            throw $this->connectionFailure($exception, 'chat');
        }

        if (! $response->successful()) {
            return $this->unsuccessful($response->status(), $model);
        }

        $content = $response->json('message.content');

        return $this->acceptAnswer(
            is_string($content) ? $this->stripReasoning($content) : '',
            $response->json('done'),
            $response->json('done_reason'),
            $financialContext
        );
    }

    public function stream(string $question, array $history, ?array $financialContext, callable $onToken, ?string $page = null): ?string
    {
        $baseUrl = $this->baseUrl();
        $model = $this->model();
        if ($baseUrl === null || $model === null) {
            return null;
        }

        $this->ensureModelIsInstalled($baseUrl, $model);

        try {
            $response = $this->client()
                ->timeout($this->prepareTimeout())
                ->withOptions(['stream' => true])
                ->post($baseUrl.'/api/chat', $this->payload($model, $question, $history, $financialContext, true, $page));
        } catch (ConnectionException $exception) {
            throw $this->connectionFailure($exception, 'chat');
        }

        if (! $response->successful()) {
            return $this->unsuccessful($response->status(), $model);
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $raw = '';
        $emitted = 0;
        $done = null;
        $doneReason = null;

        try {
            while (! $body->eof()) {
                $chunk = $body->read(1024);
                if ($chunk === '') {
                    continue;
                }

                $buffer .= $chunk;
                while (($newline = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $newline));
                    $buffer = substr($buffer, $newline + 1);
                    $data = $line === '' ? null : json_decode($line, true);
                    if (! is_array($data)) {
                        continue;
                    }

                    if (isset($data['error'])) {
                        Log::warning('Ollama stream reported an error.');

                        return null;
                    }

                    $piece = $data['message']['content'] ?? '';
                    if (is_string($piece) && $piece !== '') {
                        $raw .= $piece;
                        $visible = $this->visibleStreamText($raw);
                        if (strlen($visible) > $emitted) {
                            $onToken(substr($visible, $emitted));
                            $emitted = strlen($visible);
                        }
                    }

                    if (($data['done'] ?? false) === true) {
                        $done = true;
                        $doneReason = $data['done_reason'] ?? null;
                    }
                }
            }
        } catch (Throwable $exception) {
            throw $this->connectionFailure($exception, 'chat-stream');
        }

        return $this->acceptAnswer($this->stripReasoning($raw), $done, $doneReason, $financialContext);
    }

    private function prepareTimeout(): int
    {
        $timeout = max(5, (int) config('services.ollama.timeout', 120));
        // PHP's own limit (often 60s) must not cut off a slow local model before the HTTP timeout.
        @set_time_limit($timeout + 30);

        return $timeout;
    }

    /**
     * @param  array<int, mixed>  $history
     * @return array<string, mixed>
     */
    private function payload(string $model, string $question, array $history, ?array $financialContext, bool $stream, ?string $page = null): array
    {
        $messages = [['role' => 'system', 'content' => $this->guard->systemPrompt($page)]];
        foreach ($this->recentHistory($history) as $message) {
            $messages[] = $message;
        }
        $messages[] = ['role' => 'user', 'content' => $this->guard->userMessage($question, $financialContext)];

        return [
            'model' => $model,
            'messages' => $messages,
            'stream' => $stream,
            'keep_alive' => (string) config('services.ollama.keep_alive', '5m'),
            'options' => [
                'temperature' => (float) config('services.ollama.temperature', 0.3),
                'num_ctx' => (int) config('services.ollama.num_ctx', 4096),
                'num_predict' => (int) config('services.ollama.num_predict', 300),
            ],
        ];
    }

    private function unsuccessful(int $status, string $model): ?string
    {
        Log::warning('Ollama chat returned an unsuccessful response.', ['status' => $status]);

        if ($status === 404) {
            throw $this->missingModel($model);
        }

        return null;
    }

    private function acceptAnswer(string $answer, mixed $done, mixed $doneReason, ?array $financialContext): ?string
    {
        $answer = $this->usePesoSign($answer);

        if ($done !== true || $answer === '') {
            Log::warning('Ollama returned an invalid or ungrounded response.', [
                'done' => $done,
                'done_reason' => is_string($doneReason) ? $doneReason : null,
                'has_text' => $answer !== '',
            ]);

            return null;
        }

        if ($financialContext !== null && $this->guard->hasUnsupportedNumericClaims($answer, $financialContext)) {
            Log::info('Ollama answer contains figures not found in the verified financial context.');
        }

        return $answer;
    }

    /**
     * Text that is safe to show so far: closed and unclosed <think> blocks are hidden,
     * and a trailing partial "<think>" tag is held back until it is complete.
     */
    private function visibleStreamText(string $raw): string
    {
        $visible = preg_replace('#<think>.*?</think>#is', '', $raw) ?? $raw;
        $open = stripos($visible, '<think>');
        if ($open !== false) {
            return $this->usePesoSign(substr($visible, 0, $open));
        }

        for ($length = min(6, strlen($visible)); $length > 0; $length--) {
            if (strcasecmp(substr($visible, -$length), substr('<think>', 0, $length)) === 0) {
                return $this->usePesoSign(substr($visible, 0, -$length));
            }
        }

        // A trailing "$" may be the start of an amount whose digits have not arrived yet.
        return $this->usePesoSign(preg_replace('/\$\s*$/', '', $visible) ?? $visible);
    }

    private function usePesoSign(string $text): string
    {
        return preg_replace('/\$\s?(?=\d)/', '₱', $text) ?? $text;
    }

    private function ensureModelIsInstalled(string $baseUrl, string $model): void
    {
        try {
            $response = $this->client()->timeout(5)->get($baseUrl.'/api/tags');
        } catch (ConnectionException $exception) {
            throw $this->connectionFailure($exception, 'tags');
        }

        $models = $response->successful() ? $response->json('models') : null;
        if (! is_array($models)) {
            // Cannot verify; let the chat request report the real problem.
            Log::warning('Ollama model list could not be read.', ['status' => $response->status()]);

            return;
        }

        $wanted = str_contains($model, ':') ? $model : $model.':latest';
        $installed = collect($models)
            ->flatMap(fn ($item): array => is_array($item) ? [$item['name'] ?? null, $item['model'] ?? null] : [])
            ->filter(fn ($name): bool => is_string($name))
            ->contains(fn (string $name): bool => $name === $wanted);

        if (! $installed) {
            throw $this->missingModel($model);
        }
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout(max(1, (int) config('services.ollama.connect_timeout', 5)));
    }

    /**
     * @param  array<int, mixed>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function recentHistory(array $history): array
    {
        return collect($history)
            ->filter(fn ($message): bool => is_array($message)
                && in_array($message['role'] ?? null, ['user', 'assistant'], true)
                && is_string($message['content'] ?? null)
                && trim($message['content']) !== '')
            ->slice(-self::MAX_HISTORY_MESSAGES)
            ->map(fn (array $message): array => [
                'role' => $message['role'],
                'content' => Str::limit(trim($message['content']), self::MAX_HISTORY_MESSAGE_CHARACTERS, '…'),
            ])
            ->values()
            ->all();
    }

    private function stripReasoning(string $content): string
    {
        // Some open-weight models emit their reasoning inline between <think> tags.
        $withoutReasoning = preg_replace('#<think>.*?</think>#is', '', $content) ?? $content;

        return trim($withoutReasoning);
    }

    private function connectionFailure(Throwable $exception, string $operation): AssistantProviderException
    {
        $timedOut = stripos($exception->getMessage(), 'timed out') !== false;

        Log::warning('Ollama connection failed.', [
            'operation' => $operation,
            'exception' => $exception::class,
            'timed_out' => $timedOut,
        ]);

        return new AssistantProviderException(
            $timedOut
                ? 'SpendWise AI’s local model took too long to respond. Please try again, or ask the administrator to use a smaller model.'
                : 'SpendWise AI’s local model is not running. Please ask the administrator to start Ollama and try again.'
        );
    }

    private function missingModel(string $model): AssistantProviderException
    {
        Log::warning('Configured Ollama model is not installed.', ['model' => $model]);

        return new AssistantProviderException(
            'SpendWise AI’s local model is not installed yet. Please ask the administrator to download the configured Ollama model.'
        );
    }

    private function baseUrl(): ?string
    {
        $url = config('services.ollama.base_url');
        if (
            ! is_string($url)
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
        ) {
            return null;
        }

        return rtrim($url, '/');
    }

    private function model(): ?string
    {
        $model = config('services.ollama.model');

        return is_string($model) && trim($model) !== '' ? trim($model) : null;
    }
}
