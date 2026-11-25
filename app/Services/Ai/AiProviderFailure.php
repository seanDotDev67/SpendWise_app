<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * A failed provider attempt. The message is for logs only and never contains
 * request/response bodies; $safeMessage (optional) may be shown to the user.
 */
class AiProviderFailure extends RuntimeException
{
    public function __construct(
        public readonly AiFailureKind $kind,
        public readonly ?int $status = null,
        public readonly ?int $retryAfter = null,
        public readonly ?string $safeMessage = null,
    ) {
        parent::__construct('AI provider failure: '.$kind->value.($status !== null ? ' (HTTP '.$status.')' : ''));
    }
}
