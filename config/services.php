<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI') ?: rtrim(env('APP_URL', 'http://localhost'), '/').'/auth/google/callback',
    ],

    'assistant' => [
        // "ollama" (local, default) or "gemini" (optional, explicit opt-in only).
        'provider' => env('ASSISTANT_PROVIDER', 'ollama'),
    ],

    'ollama' => [
        // "localhost" is the machine running Laravel, not the visitor's browser.
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),
        'connect_timeout' => (int) env('OLLAMA_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', '5m'),
        'num_ctx' => (int) env('OLLAMA_NUM_CTX', 4096),
        'num_predict' => (int) env('OLLAMA_NUM_PREDICT', 300),
        'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.3),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],

];
