<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;

/**
 * Cache-backed provider health: cooldowns, consecutive failures and recent
 * failure reasons. Uses the shared cache store so all web instances agree.
 */
class ProviderHealthTracker
{
    private const MAX_RECENT_FAILURES = 10;

    private const MAX_RETRY_AFTER = 3600;

    public function isAvailable(string $provider): bool
    {
        return ($this->state($provider)['cooldown_until'] ?? 0) <= now()->timestamp;
    }

    public function recordSuccess(string $provider): void
    {
        $state = $this->state($provider);
        $state['cooldown_until'] = 0;
        $state['consecutive_failures'] = 0;
        $state['last_success_at'] = now()->timestamp;

        $this->save($provider, $state);
    }

    public function recordFailure(string $provider, AiFailureKind $kind, ?int $status = null, ?int $retryAfter = null): void
    {
        $state = $this->state($provider);
        $state['consecutive_failures'] = ($state['consecutive_failures'] ?? 0) + 1;

        $cooldown = $kind === AiFailureKind::RateLimited && $retryAfter !== null
            ? min($retryAfter, self::MAX_RETRY_AFTER)
            : max(0, (int) config('ai.cooldowns.'.$kind->value, 0));

        if ($cooldown > 0) {
            $state['cooldown_until'] = now()->timestamp + $cooldown;
        }

        $recent = $state['recent_failures'] ?? [];
        $recent[] = ['kind' => $kind->value, 'status' => $status, 'at' => now()->timestamp];
        $state['recent_failures'] = array_slice($recent, -self::MAX_RECENT_FAILURES);

        $this->save($provider, $state);
    }

    /**
     * @return array{cooldown_until: int, consecutive_failures: int, last_success_at: int|null, recent_failures: array<int, array{kind: string, status: int|null, at: int}>}
     */
    public function snapshot(string $provider): array
    {
        return $this->state($provider) + [
            'cooldown_until' => 0,
            'consecutive_failures' => 0,
            'last_success_at' => null,
            'recent_failures' => [],
        ];
    }

    public function reset(string $provider): void
    {
        Cache::forget($this->key($provider));
    }

    /** @return array<string, mixed> */
    private function state(string $provider): array
    {
        $state = Cache::get($this->key($provider));

        return is_array($state) ? $state : [];
    }

    /** @param  array<string, mixed>  $state */
    private function save(string $provider, array $state): void
    {
        Cache::put($this->key($provider), $state, now()->addDay());
    }

    private function key(string $provider): string
    {
        return 'ai:health:'.$provider;
    }
}
