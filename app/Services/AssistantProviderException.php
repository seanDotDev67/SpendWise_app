<?php

namespace App\Services;

use RuntimeException;

/**
 * Failure whose message is safe to show to the end user. Technical details
 * belong in server logs, never in this message.
 */
class AssistantProviderException extends RuntimeException {}
