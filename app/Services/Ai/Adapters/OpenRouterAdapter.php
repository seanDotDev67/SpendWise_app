<?php

namespace App\Services\Ai\Adapters;

/** OpenRouter (OpenAI-compatible endpoint with attribution headers). */
class OpenRouterAdapter extends OpenAiCompatibleAdapter
{
    public function isConfigured(): bool
    {
        if (! parent::isConfigured()) {
            return false;
        }

        // Never route to a paid model unless that was explicitly allowed.
        return str_ends_with((string) $this->model(), ':free')
            || ($this->config['allow_paid_models'] ?? false) === true;
    }

    protected function headers(): array
    {
        $headers = parent::headers();
        if (is_string($this->config['referer'] ?? null) && $this->config['referer'] !== '') {
            $headers['HTTP-Referer'] = $this->config['referer'];
        }
        if (is_string($this->config['title'] ?? null) && $this->config['title'] !== '') {
            $headers['X-Title'] = $this->config['title'];
        }

        return $headers;
    }
}
