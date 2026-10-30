<?php

namespace App\View\Composers;

use App\Contracts\AssistantProvider;
use App\Services\AssistantResponseGuard;
use App\Services\SpendingHabitAnalyzer;
use App\Services\SpendWiseAssistant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Supplies the floating AI companion with server-side data only: the current page,
 * the saved conversation, and an alert when a spending pattern deserves attention.
 */
class AiCompanionComposer
{
    public function __construct(
        private readonly AssistantProvider $provider,
        private readonly SpendingHabitAnalyzer $habits
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        if ($user === null) {
            $view->with('companion', ['enabled' => false]);

            return;
        }

        $history = session(SpendWiseAssistant::HISTORY_KEY_PREFIX.$user->getAuthIdentifier(), []);
        $history = is_array($history) ? array_slice($history, -12) : [];

        $alert = Cache::remember(
            'ai_companion_alert:'.$user->getAuthIdentifier().':'.now()->toDateString(),
            600,
            function () use ($user): ?array {
                $lead = $this->habits->analyze($user, now())['lead'];

                return $lead !== null && $lead['tone'] === 'warning'
                    ? ['type' => $lead['type'], 'title' => $lead['title']]
                    : null;
            }
        );

        $view->with('companion', [
            'enabled' => true,
            'page' => $this->currentPage(),
            'history' => $history,
            'alert' => $alert,
            'configured' => $this->provider->isConfigured(),
        ]);
    }

    private function currentPage(): ?string
    {
        $name = (string) request()->route()?->getName();

        foreach (array_keys(AssistantResponseGuard::PAGES) as $page) {
            if ($name === $page || Str::startsWith($name, $page.'.')) {
                return $page;
            }
        }

        return null;
    }
}
