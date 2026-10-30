<?php

namespace App\Services;

/**
 * Builds chart descriptions from the verified financial context (never from model text),
 * so every plotted number comes straight from the user's recorded data.
 *
 * Chart shape: {type, title, labels[], datasets[{label, data[], color?, colors?}], kind, note?}
 * where type is one of bar, line, doughnut, comparison or progress.
 */
class AssistantVisuals
{
    private const TREND_WORDS = '/\b(trend|trends|over time|monthly|month by month|history|progress|moving|going up|going down|pattern)\b|\b(?:last|past)\s+(?:\d{1,2}|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)\s+months?\b/i';

    private const RANKING_WORDS = '/\b(most|highest|largest|biggest|top|consumes?)\b/i';

    /**
     * @param  array<string, mixed>|null  $context
     * @return array<int, array<string, mixed>>
     */
    public function fromContext(?array $context, string $question): array
    {
        if ($context === null) {
            return [];
        }

        $visuals = [];
        $income = (float) ($context['recorded_totals']['income'] ?? 0);
        $expenses = (float) ($context['recorded_totals']['expenses'] ?? 0);
        $categories = $context['expense_categories'] ?? [];
        $monthly = $context['monthly_totals'] ?? [];
        $comparison = $context['category_comparison'] ?? [];
        $budgetStatus = $context['budget_status'] ?? null;

        if (isset($context['simulation'])) {
            $visuals[] = $this->simulation($context['simulation']);
        }

        $series = $context['spending_by_day'] ?? $context['spending_by_week'] ?? null;
        if (is_array($budgetStatus) && ! empty($budgetStatus['available'])) {
            $visuals[] = $this->budgetUtilization($budgetStatus);
        } elseif ($series !== null && count($series) >= 2) {
            $visuals[] = $this->spendingSeries($series, isset($context['spending_by_day']));
        } elseif (isset($context['category_focus'])) {
            $visuals[] = $this->categoryFocus($context['category_focus']);
            if ($categories !== []) {
                $visuals[] = $this->categoryDonut($categories);
            }
        } elseif ($comparison !== []) {
            $visuals[] = $this->periodComparison($context);
            $visuals[] = $this->categoryComparison($comparison);
        } elseif (count($monthly) >= 2 && preg_match(self::TREND_WORDS, $question) === 1) {
            $visuals[] = $this->trend($monthly);
        } elseif (! isset($context['simulation'])) {
            if ($income > 0 || $expenses > 0) {
                $visuals[] = $this->incomeVersusExpenses($income, $expenses);
            }
            if ($categories !== []) {
                $visuals[] = preg_match(self::RANKING_WORDS, $question) === 1
                    ? $this->categoryBars($categories)
                    : $this->categoryDonut($categories);
            }
            if (count($monthly) >= 2 && $visuals === []) {
                $visuals[] = $this->trend($monthly);
            }
        }

        if (isset($context['income_usage'])) {
            $visuals[] = $this->incomeUsage($context['income_usage']);
        }

        return array_values(array_filter($visuals));
    }

    /** @return array<string, mixed> */
    private function incomeVersusExpenses(float $income, float $expenses): array
    {
        return [
            'type' => 'comparison',
            'title' => 'Income vs expenses',
            'labels' => ['Income', 'Expenses'],
            'datasets' => [['label' => 'Amount', 'data' => [$income, $expenses], 'colors' => ['#2F704F', '#D9534F']]],
            'kind' => 'actual',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    private function categoryDonut(array $categories): array
    {
        $top = array_slice($categories, 0, 6);

        return [
            'type' => 'doughnut',
            'title' => 'Where your money went',
            'labels' => array_map(fn (array $c): string => (string) $c['name'], $top),
            'datasets' => [['label' => 'Spent', 'data' => array_map(fn (array $c): float => (float) $c['spent'], $top)]],
            'kind' => 'actual',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    private function categoryBars(array $categories): array
    {
        $top = array_slice($categories, 0, 6);

        return [
            'type' => 'bar',
            'title' => 'Expenses by category',
            'labels' => array_map(fn (array $c): string => (string) $c['name'], $top),
            'datasets' => [['label' => 'Spent', 'data' => array_map(fn (array $c): float => (float) $c['spent'], $top), 'color' => '#2F704F']],
            'kind' => 'actual',
        ];
    }

    /**
     * @param  array<string, mixed>  $focus
     * @return array<string, mixed>
     */
    private function categoryFocus(array $focus): array
    {
        return [
            'type' => 'progress',
            'title' => 'Share of your expenses',
            'labels' => [(string) $focus['name']],
            'datasets' => [['label' => 'Share of expenses (%)', 'data' => [(float) $focus['share_percent']]]],
            'kind' => 'actual',
        ];
    }

    /**
     * @param  array<string, mixed>  $budgetStatus
     * @return array<string, mixed>
     */
    private function budgetUtilization(array $budgetStatus): array
    {
        $categories = $budgetStatus['categories'];

        return [
            'type' => 'progress',
            'title' => 'Monthly budget use — '.$budgetStatus['month'],
            'labels' => array_map(fn (array $category): string => (string) $category['name'], $categories),
            'datasets' => [[
                'label' => 'Budget used (%)',
                'data' => array_map(fn (array $category): float => (float) $category['used_percent'], $categories),
            ]],
            'kind' => 'actual',
            'note' => 'Current-month recorded spending compared with saved monthly category limits.',
        ];
    }

    /**
     * @param  array<string, mixed>  $usage
     * @return array<string, mixed>
     */
    private function incomeUsage(array $usage): array
    {
        return [
            'type' => 'progress',
            'title' => 'How your income is used',
            'labels' => ['Spent from income', 'Savings rate'],
            'datasets' => [['label' => 'Percent of income', 'data' => [
                (float) $usage['expenses_percent_of_income'],
                max(0.0, (float) $usage['savings_rate_percent']),
            ]]],
            'kind' => 'actual',
            'note' => 'Calculated from recorded income and expenses; separate from category budget limits.',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $series
     * @return array<string, mixed>
     */
    private function spendingSeries(array $series, bool $daily): array
    {
        return [
            'type' => 'line',
            'title' => $daily ? 'Daily spending' : 'Weekly spending',
            'labels' => array_map(fn (array $point): string => (string) $point['label'], $series),
            'datasets' => [['label' => 'Expenses', 'color' => '#D9534F', 'data' => array_map(fn (array $point): float => (float) $point['spent'], $series)]],
            'kind' => 'actual',
        ];
    }

    /**
     * @param  array<string, mixed>  $simulation
     * @return array<string, mixed>
     */
    private function simulation(array $simulation): array
    {
        return [
            'type' => 'comparison',
            'title' => 'Simulated: '.$simulation['applies_to'].' reduced by '.(float) $simulation['reduction_percent'].'%',
            'labels' => [ucfirst((string) $simulation['applies_to']).' spending', 'Net cash flow'],
            'datasets' => [
                ['label' => 'Recorded', 'color' => '#A8B1A9', 'data' => [(float) $simulation['actual_spent'], (float) $simulation['actual_net']]],
                ['label' => 'Simulated', 'color' => '#D9A441', 'data' => [(float) $simulation['simulated_spent'], (float) $simulation['simulated_net']]],
            ],
            'kind' => 'simulation',
            'note' => 'Hypothetical projection based on your recorded spending. It is not a recorded result.',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $monthly
     * @return array<string, mixed>
     */
    private function trend(array $monthly): array
    {
        return [
            'type' => 'line',
            'title' => 'Monthly income and expenses',
            'labels' => array_map(fn (array $m): string => (string) $m['month'], $monthly),
            'datasets' => [
                ['label' => 'Income', 'color' => '#2F704F', 'data' => array_map(fn (array $m): float => (float) $m['income'], $monthly)],
                ['label' => 'Expenses', 'color' => '#D9534F', 'data' => array_map(fn (array $m): float => (float) $m['expenses'], $monthly)],
            ],
            'kind' => 'actual',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function periodComparison(array $context): array
    {
        $previous = $context['previous_period'] ?? [];

        return [
            'type' => 'comparison',
            'title' => 'This period vs previous',
            'labels' => ['Income', 'Expenses'],
            'datasets' => [
                ['label' => 'Previous', 'color' => '#A8B1A9', 'data' => [(float) ($previous['income'] ?? 0), (float) ($previous['expenses'] ?? 0)]],
                ['label' => 'Current', 'color' => '#2F4A3A', 'data' => [
                    (float) ($context['recorded_totals']['income'] ?? 0),
                    (float) ($context['recorded_totals']['expenses'] ?? 0),
                ]],
            ],
            'kind' => 'actual',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $comparison
     * @return array<string, mixed>
     */
    private function categoryComparison(array $comparison): array
    {
        $top = collect($comparison)
            ->sortByDesc(fn (array $c): float => max((float) $c['current_spent'], (float) $c['previous_spent']))
            ->take(6)
            ->values();

        return [
            'type' => 'comparison',
            'title' => 'Spending by category',
            'labels' => $top->pluck('name')->map(fn ($n): string => (string) $n)->all(),
            'datasets' => [
                ['label' => 'Previous', 'color' => '#A8B1A9', 'data' => $top->map(fn (array $c): float => (float) $c['previous_spent'])->all()],
                ['label' => 'Current', 'color' => '#2F4A3A', 'data' => $top->map(fn (array $c): float => (float) $c['current_spent'])->all()],
            ],
            'kind' => 'actual',
        ];
    }
}
