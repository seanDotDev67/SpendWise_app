<?php

namespace App\Console\Commands;

use App\Services\Ai\AiProviderManager;
use App\Services\Ai\ProviderHealthTracker;
use Illuminate\Console\Command;

class AiProvidersStatus extends Command
{
    protected $signature = 'ai:status {--reset= : Clear health state (cooldown) for the named provider}';

    protected $description = 'Show configured AI providers, their priority and recent health';

    public function handle(AiProviderManager $manager, ProviderHealthTracker $health): int
    {
        if (is_string($reset = $this->option('reset')) && $reset !== '') {
            $health->reset($reset);
            $this->info("Health state cleared for {$reset}.");
        }

        $rows = [];
        foreach ($manager->adapters() as $adapter) {
            $state = $health->snapshot($adapter->name());
            $last = collect($state['recent_failures'])->last();
            $rows[] = [
                $adapter->name(),
                $adapter->model(),
                $health->isAvailable($adapter->name()) ? 'available' : 'cooling down until '.date('H:i:s', $state['cooldown_until']),
                $state['consecutive_failures'],
                $last['kind'] ?? '-',
            ];
        }

        $rows === []
            ? $this->warn('No AI provider is configured.')
            : $this->table(['Provider', 'Model', 'State', 'Consecutive failures', 'Last failure'], $rows);

        return self::SUCCESS;
    }
}
