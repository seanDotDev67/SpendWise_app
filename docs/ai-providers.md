# SpendWise AI providers and automatic failover

SpendWise AI can use several model providers. When one hits its quota, is down, or times out, the request moves to the next provider automatically.

## How it works

```
SpendWiseAssistant -> AssistantProvider (AiProviderManager) -> adapters, in priority order
                                                              1. gemini      (primary)
                                                              2. groq        (secondary)
                                                              3. openrouter  (third)
                                                              4. ollama      (local development only)
```

- `SpendWiseAssistant`, the controllers, `AssistantResponseGuard`, `FinancialInsightService`, `AssistantVisuals` and `SavingsProjectionService` are unchanged. The manager sits behind the existing `AssistantProvider` contract.
- The system prompt, the trimmed history and the financial context are built **once** and sent unchanged to whichever provider answers. Each adapter translates them into its own API format.
- Every answer is validated before it is returned: peso formatting, length limit, figures checked against the verified financial context (`AssistantResponseGuard::hasUnsupportedNumericClaims`) and the savings-goal guard. A failed validation counts as a failed attempt and moves to the next provider.
- If every provider fails, the user gets a safe error (HTTP 503). No answer is ever invented.
- Only the model call is covered. No database or financial operation is retried.

### Failure handling

| Failure | Kind | Cooldown (default) | Moves to next provider |
|---|---|---|---|
| HTTP 429 | `rate_limited` | `Retry-After` if sent, else 60s | yes |
| HTTP 5xx / 503 | `unavailable` | 30s | yes |
| Timeout | `timeout` | 30s | yes |
| Connection failure | `connection` | 30s | yes |
| HTTP 401 / 403 | `auth` | 600s | yes, and never retried until the cooldown ends |
| HTTP 400 / 404 / 422 | `invalid_request` | 600s | yes, and never retried until the cooldown ends |
| Empty, truncated or ungrounded answer | `bad_response` | none | yes |
| Safety block | `blocked` | none | yes |

Each provider is tried at most once per request, and `AI_MAX_ATTEMPTS` (default 3) caps the total, so there are no retry loops. A provider in cooldown is skipped until it expires, and a success clears its state.

Streaming fails over only before the first token is sent. After that, a failure ends the stream with an error, because switching providers would corrupt the answer. Gemini, Groq and OpenRouter deliver the answer as one chunk. Ollama still streams.

## Configuration

All settings are in [`config/ai.php`](../config/ai.php): priority, model, timeouts, token limits and cooldowns. Secrets come from environment variables only and are never sent to the browser or written to logs.

| Variable | Purpose |
|---|---|
| `ASSISTANT_PROVIDER` | `failover` (default). `ollama` or `gemini` keep the old single-provider behaviour with no fallback. |
| `GEMINI_API_KEY`, `GROQ_API_KEY`, `OPENROUTER_API_KEY` | Keys. A provider with no key is skipped. |
| `AI_GEMINI_MODEL`, `AI_GROQ_MODEL`, `AI_OPENROUTER_MODEL` | Model names (defaults in `config/ai.php`). |
| `AI_<PROVIDER>_PRIORITY` | Lower number is tried first. |
| `AI_<PROVIDER>_ENABLED` | Turn a provider off without removing its key. |
| `AI_MAX_ATTEMPTS` | Maximum providers tried per request. |
| `AI_COOLDOWN_*` | Cooldown seconds per failure kind. |
| `AI_OPENROUTER_ALLOW_PAID` | Must be `true` to use a model that does not end in `:free`. |
| `AI_OLLAMA_ENABLED` | Ollama is on automatically when `APP_ENV=local` or `testing`. Set `true` to force it elsewhere. |

**Cost safety:** SpendWise never selects a paid OpenRouter model unless `AI_OPENROUTER_ALLOW_PAID=true`. Free-tier limits for Gemini and Groq depend on your project or account. Check that billing is not enabled there if you want to avoid charges.

## Monitoring

Each attempt writes one structured record to the `ai` log channel (it follows `LOG_STACK`, or set `AI_LOG_STACK`):

`provider`, `model`, `status` (`ok`/`failed`), `latency_ms`, `attempt`, `failover_attempts`, `failure_kind`, `http_status`, `prompt_tokens`, `completion_tokens`, `total_tokens`.

Prompts, answers, financial data, headers and API keys are never logged. If every provider fails, one `All AI providers failed.` record is written.

Check provider health at any time:

```
php artisan ai:status                 # provider, model, state, consecutive failures, last failure
php artisan ai:status --reset=gemini  # clear a cooldown after fixing a key
```

Health state lives in the Laravel cache.

## Deploying on Render

1. In the web service, open **Environment** and add `GEMINI_API_KEY`, `GROQ_API_KEY` and `OPENROUTER_API_KEY` as secrets. Add only the providers you want.
2. Leave `ASSISTANT_PROVIDER` unset (or `failover`). Set `APP_ENV=production`, so Ollama stays off. Render cannot reach an Ollama server on your PC.
3. Keep `CACHE_STORE=database` (or `redis`). Cooldowns must be shared by every instance. Do not use `array`. Make sure the `cache` table migration has run.
4. Optionally set `AI_LOG_STACK=stderr` so records show up in Render's log stream. Define a `stderr` channel in `config/logging.php` if you don't have one.
5. Redeploy, then run `php artisan ai:status` in the Render shell. Every provider you configured should be listed as `available`.
6. Rotate a key by changing the environment variable and redeploying. Then run `php artisan ai:status --reset=<provider>`.

## Adding another provider

1. **Adapter.** Create `app/Services/Ai/Adapters/MyAdapter.php`.
   - If the vendor exposes an OpenAI-style `/chat/completions` API, extend `OpenAiCompatibleAdapter` (see `GroqAdapter`) and override only what differs.
   - Otherwise extend `AbstractHttpAdapter` and implement `url()`, `headers()`, `payload(AiRequest)` and `parse(Response)`. Throw `AiProviderFailure` with the right `AiFailureKind` for unusable responses. HTTP errors, timeouts and connection failures are already classified by the base class.
   - To use a different transport, implement `App\Contracts\AiProviderAdapter` directly. Implement `StreamingAiProviderAdapter` too if it can stream.
2. **Config.** Add an entry to `providers` in `config/ai.php` with `adapter`, `enabled`, `priority`, `api_key`, `model`, `endpoint`, timeouts and token limits. Read every secret with `env()`. The `priority` number sets its place in the chain.
3. **Environment.** Add the key variable to `.env.example` and to this document.
4. **Tests.** Copy the patterns in `tests/Unit/Ai/AiAdaptersTest.php`. Cover request mapping, response parsing, error classification and key/endpoint validation, all with `Http::fake`. The failover tests in `tests/Feature/AiFailoverTest.php` apply to it automatically.

No change to the manager, the controllers or `SpendWiseAssistant` is needed.
