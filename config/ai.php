<?php

use App\Services\Ai\Adapters\GeminiAdapter;
use App\Services\Ai\Adapters\GroqAdapter;
use App\Services\Ai\Adapters\OllamaAdapter;
use App\Services\Ai\Adapters\OpenRouterAdapter;

/*
|--------------------------------------------------------------------------
| SpendWise AI provider orchestration
|--------------------------------------------------------------------------
|
| Used when ASSISTANT_PROVIDER=failover (the default). Providers are tried in
| ascending "priority" order; a provider without an API key is skipped.
| Credentials stay server-side and are never sent to the browser or logged.
| To add a provider, see docs/ai-providers.md.
|
*/

return [

    // Maximum number of providers tried for a single request (no same-provider retries).
    'max_attempts' => (int) env('AI_MAX_ATTEMPTS', 3),

    'max_answer_characters' => 4000,

    // Treat an answer with figures absent from the verified financial context as a failed attempt.
    'reject_unsupported_numbers' => (bool) env('AI_REJECT_UNSUPPORTED_NUMBERS', true),

    // Logging channel for provider monitoring (see config/logging.php).
    'log_channel' => env('AI_LOG_CHANNEL', 'ai'),

    // Seconds a provider is skipped after a failure of the given kind.
    // A 429 response's Retry-After header, when present, takes precedence.
    'cooldowns' => [
        'rate_limited' => (int) env('AI_COOLDOWN_RATE_LIMITED', 60),
        'unavailable' => (int) env('AI_COOLDOWN_UNAVAILABLE', 30),
        'timeout' => (int) env('AI_COOLDOWN_TIMEOUT', 30),
        'connection' => (int) env('AI_COOLDOWN_CONNECTION', 30),
        'auth' => (int) env('AI_COOLDOWN_AUTH', 600),
        'invalid_request' => (int) env('AI_COOLDOWN_INVALID_REQUEST', 600),
        'bad_response' => 0,
        'blocked' => 0,
    ],

    'providers' => [

        'gemini' => [
            'adapter' => GeminiAdapter::class,
            'enabled' => (bool) env('AI_GEMINI_ENABLED', true),
            'priority' => (int) env('AI_GEMINI_PRIORITY', 1),
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('AI_GEMINI_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash')),
            'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
            'connect_timeout' => (int) env('AI_GEMINI_CONNECT_TIMEOUT', 5),
            'timeout' => (int) env('AI_GEMINI_TIMEOUT', 20),
            'max_output_tokens' => (int) env('AI_GEMINI_MAX_OUTPUT_TOKENS', 2048),
            'temperature' => (float) env('AI_GEMINI_TEMPERATURE', 0.3),
        ],

        'groq' => [
            'adapter' => GroqAdapter::class,
            'enabled' => (bool) env('AI_GROQ_ENABLED', true),
            'priority' => (int) env('AI_GROQ_PRIORITY', 2),
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('AI_GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'endpoint' => env('GROQ_API_ENDPOINT', 'https://api.groq.com/openai/v1'),
            'connect_timeout' => (int) env('AI_GROQ_CONNECT_TIMEOUT', 5),
            'timeout' => (int) env('AI_GROQ_TIMEOUT', 20),
            'max_output_tokens' => (int) env('AI_GROQ_MAX_OUTPUT_TOKENS', 1024),
            'temperature' => (float) env('AI_GROQ_TEMPERATURE', 0.3),
        ],

        'openrouter' => [
            'adapter' => OpenRouterAdapter::class,
            'enabled' => (bool) env('AI_OPENROUTER_ENABLED', true),
            'priority' => (int) env('AI_OPENROUTER_PRIORITY', 3),
            'api_key' => env('OPENROUTER_API_KEY'),
            // Defaults to a free model. Paid models are refused unless explicitly allowed below.
            'model' => env('AI_OPENROUTER_MODEL', 'google/gemma-4-31b-it:free'),
            'allow_paid_models' => (bool) env('AI_OPENROUTER_ALLOW_PAID', false),
            'endpoint' => env('OPENROUTER_API_ENDPOINT', 'https://openrouter.ai/api/v1'),
            'referer' => env('APP_URL'),
            'title' => 'SpendWise',
            'connect_timeout' => (int) env('AI_OPENROUTER_CONNECT_TIMEOUT', 5),
            'timeout' => (int) env('AI_OPENROUTER_TIMEOUT', 30),
            'max_output_tokens' => (int) env('AI_OPENROUTER_MAX_OUTPUT_TOKENS', 1024),
            'temperature' => (float) env('AI_OPENROUTER_TEMPERATURE', 0.3),
        ],

        // Local development only: enabled automatically in "local"/"testing"
        // environments, or anywhere when AI_OLLAMA_ENABLED=true. Settings live in services.ollama.
        'ollama' => [
            'adapter' => OllamaAdapter::class,
            'enabled' => env('AI_OLLAMA_ENABLED'),
            'priority' => (int) env('AI_OLLAMA_PRIORITY', 4),
        ],
    ],
];
