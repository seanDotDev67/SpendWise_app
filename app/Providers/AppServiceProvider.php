<?php

namespace App\Providers;

use App\Contracts\AssistantProvider;
use App\Services\GeminiReportResponder;
use App\Services\OllamaChatProvider;
use App\View\Composers\AiCompanionComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AssistantProvider::class, fn ($app): AssistantProvider => match (config('services.assistant.provider')) {
            'gemini' => $app->make(GeminiReportResponder::class),
            default => $app->make(OllamaChatProvider::class),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.partials.ai-companion', AiCompanionComposer::class);
    }
}
