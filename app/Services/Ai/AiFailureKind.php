<?php

namespace App\Services\Ai;

enum AiFailureKind: string
{
    case RateLimited = 'rate_limited';
    case Unavailable = 'unavailable';
    case Timeout = 'timeout';
    case Connection = 'connection';
    case Auth = 'auth';
    case InvalidRequest = 'invalid_request';
    case BadResponse = 'bad_response';
    case Blocked = 'blocked';

    /** Failures caused by our configuration or request; retrying cannot help until fixed. */
    public function isConfigurationProblem(): bool
    {
        return $this === self::Auth || $this === self::InvalidRequest;
    }

    public static function fromHttpStatus(int $status): self
    {
        return match (true) {
            $status === 429 => self::RateLimited,
            $status === 408 => self::Timeout,
            $status === 401 || $status === 403 => self::Auth,
            $status >= 500 => self::Unavailable,
            default => self::InvalidRequest,
        };
    }
}
