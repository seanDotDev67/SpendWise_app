<?php

namespace App\Services\Ai\Adapters;

use App\Contracts\AiProviderAdapter;
use App\Services\Ai\AiFailureKind;
use App\Services\Ai\AiProviderFailure;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Shared HTTP plumbing and error classification. Subclasses define each
 * vendor's own request and response format.
 */
abstract class AbstractHttpAdapter implements AiProviderAdapter
{
    /** @param  array<string, mixed>  $config */
    public function __construct(protected readonly string $name, protected readonly array $config) {}

    public function name(): string
    {
        return $this->name;
    }

    public function model(): ?string
    {
        $model = $this->config['model'] ?? null;

        return is_string($model) && trim($model) !== '' ? trim($model) : null;
    }

    public function isConfigured(): bool
    {
        $key = $this->config['api_key'] ?? null;
        $endpoint = $this->config['endpoint'] ?? null;

        return ($this->config['enabled'] ?? true) !== false
            && is_string($key) && trim($key) !== ''
            && $this->model() !== null
            && is_string($endpoint)
            && filter_var($endpoint, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($endpoint, PHP_URL_SCHEME)) === 'https';
    }

    public function complete(AiRequest $request): AiResult
    {
        $started = hrtime(true);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders($this->headers())
                ->connectTimeout(max(1, (int) ($this->config['connect_timeout'] ?? 5)))
                ->timeout(max(1, (int) ($this->config['timeout'] ?? 20)))
                ->post($this->url(), $this->payload($request));
        } catch (ConnectionException $exception) {
            $timedOut = stripos($exception->getMessage(), 'timed out') !== false;

            throw new AiProviderFailure($timedOut ? AiFailureKind::Timeout : AiFailureKind::Connection);
        } catch (Throwable) {
            throw new AiProviderFailure(AiFailureKind::Connection);
        }

        if (! $response->successful()) {
            throw new AiProviderFailure(
                AiFailureKind::fromHttpStatus($response->status()),
                $response->status(),
                $this->retryAfter($response)
            );
        }

        return $this->parse($response);
    }

    abstract protected function url(): string;

    /** @return array<string, string> */
    abstract protected function headers(): array;

    /** @return array<string, mixed> */
    abstract protected function payload(AiRequest $request): array;

    /** @throws AiProviderFailure */
    abstract protected function parse(Response $response): AiResult;

    protected function retryAfter(Response $response): ?int
    {
        $value = $response->header('Retry-After');

        return is_numeric($value) ? max(0, (int) $value) : null;
    }

    protected function stripReasoning(string $text): string
    {
        // Some open-weight models emit their reasoning inline between <think> tags.
        return trim(preg_replace('#<think>.*?</think>#is', '', $text) ?? $text);
    }

    protected function intOrNull(mixed $value): ?int
    {
        return is_int($value) ? $value : null;
    }
}
