<?php

namespace App\Services;

/**
 * Assembles the structured assistant response (message, summary, charts, insights,
 * recommendations, severity). Charts, figures and insights are derived in PHP from the verified
 * financial context; the model only contributes the explanation and optional recommendations.
 */
class FinancialInsightService
{
    public function __construct(
        private readonly AssistantVisuals $visuals,
        private readonly InsightResponseValidator $validator,
        private readonly AssistantResponseGuard $guard
    ) {}

    /**
     * @param  array<string, mixed>|null  $context  Verified context; null for non-financial questions.
     * @return array<string, mixed>|null
     */
    public function build(?array $context, string $question, string $modelOutput): ?array
    {
        if ($context === null) {
            return null;
        }

        $parsed = $this->validator->parseModelOutput($modelOutput);
        $facts = $this->facts($context);
        $hasBudgets = ! empty($context['budget_status']['available']);
        $insufficient = (int) ($context['recorded_totals']['transaction_count'] ?? 0) === 0 && ! $hasBudgets;

        $payload = $insufficient
            ? [
                'message' => $parsed['message'],
                'summary' => 'There are no recorded transactions for this period yet.',
                'charts' => [],
                'insights' => ['SpendWise needs some recorded income or expenses before it can chart this.'],
                'recommendations' => ['Add your transactions, or widen the date range, then ask again.'],
                'severity' => 'informational',
            ]
            : [
                'message' => $parsed['message'],
                'summary' => $facts['summary'],
                'charts' => $this->visuals->fromContext($context, $question),
                'insights' => $facts['insights'],
                'recommendations' => [...$parsed['recommendations'], ...$facts['recommendations']],
                'severity' => $facts['severity'],
            ];

        $response = $this->validator->response($payload);
        if ($response === null) {
            return null;
        }

        $response['recommendations'] = array_slice($response['recommendations'], 0, 4);
        $response['insufficient_data'] = $insufficient;
        $response['unverified_figures'] = $this->guard->hasUnsupportedNumericClaims(
            implode(' ', [$response['message'], ...$parsed['recommendations']]),
            $context
        );

        return $response;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{summary: string, insights: array<int, string>, recommendations: array<int, string>, severity: string}
     */
    private function facts(array $context): array
    {
        $totals = $context['recorded_totals'];
        $income = (float) $totals['income'];
        $expenses = (float) $totals['expenses'];
        $net = (float) $totals['net_cash_flow'];
        $range = $context['selected_date_range'] ?? [];
        $categories = $context['expense_categories'] ?? [];

        $insights = [];
        $recommendations = [];
        $severity = 'informational';

        $summary = sprintf(
            'Recorded income %s, expenses %s, net %s (%s to %s).',
            $this->peso($income),
            $this->peso($expenses),
            $this->peso($net),
            $range['start'] ?? '?',
            $range['end'] ?? '?'
        );

        if (isset($context['simulation'])) {
            $sim = $context['simulation'];
            $insights[] = sprintf(
                'Projection: cutting %s by %s%% would free up about %s in this period.',
                $sim['applies_to'],
                (float) $sim['reduction_percent'],
                $this->peso((float) $sim['projected_savings'])
            );
            $recommendations[] = 'If this cut feels realistic, set it as a target and review your progress next month.';
        }

        if (isset($context['category_focus'])) {
            $focus = $context['category_focus'];
            $insights[] = sprintf(
                '%s: %s across %d transaction(s), %s%% of your expenses.',
                $focus['name'],
                $this->peso((float) $focus['spent']),
                $focus['transaction_count'],
                $focus['share_percent']
            );
        }

        if ($categories !== []) {
            $top = $categories[0];
            $insights[] = sprintf(
                '%s is your largest expense category at %s%% of spending (%s).',
                $top['name'],
                $top['share_percent'],
                $this->peso((float) $top['spent'])
            );
            if ((float) $top['share_percent'] >= 40) {
                $recommendations[] = sprintf('Consider setting a spending limit for %s, your largest category.', $top['name']);
            }
        }

        if (isset($context['budget_status'])) {
            $budgetStatus = $context['budget_status'];
            if (! empty($budgetStatus['available'])) {
                $summary .= sprintf(
                    ' Current monthly budgets: %s limit, %s spent, %s remaining (%s).',
                    $this->peso((float) $budgetStatus['total_monthly_limit']),
                    $this->peso((float) $budgetStatus['total_spent']),
                    $this->peso((float) $budgetStatus['total_remaining']),
                    $budgetStatus['month']
                );

                foreach ($budgetStatus['categories'] as $budget) {
                    $insights[] = sprintf(
                        '%s: %s of %s used (%s%%) for %s.',
                        $budget['name'],
                        $this->peso((float) $budget['spent']),
                        $this->peso((float) $budget['monthly_limit']),
                        $budget['used_percent'],
                        $budgetStatus['month']
                    );
                    if ($budget['status'] === 'over') {
                        $severity = 'warning';
                        $recommendations[] = sprintf(
                            '%s is %s over its monthly limit; review spending in this category.',
                            $budget['name'],
                            $this->peso(abs((float) $budget['remaining']))
                        );
                    }
                }
            } else {
                $insights[] = 'No monthly category budgets have been set yet.';
                $recommendations[] = 'Set monthly limits for expense categories on the Budgets page to track budget use.';
            }
        }

        $day = collect($context['spending_by_day'] ?? [])->sortByDesc(fn (array $p): float => (float) $p['spent'])->first();
        if ($day !== null && (float) $day['spent'] > 0) {
            $insights[] = sprintf('Your highest spending day was %s (%s).', $day['label'], $this->peso((float) $day['spent']));
        }

        $rise = collect($context['category_comparison'] ?? [])
            ->filter(fn (array $c): bool => (float) $c['change'] > 0)
            ->sortByDesc(fn (array $c): float => (float) $c['change'])
            ->first();
        if ($rise !== null) {
            $insights[] = sprintf(
                '%s spending rose by %s%s compared with the previous period.',
                $rise['name'],
                $this->peso((float) $rise['change']),
                $rise['change_percent'] !== null ? " ({$rise['change_percent']}%)" : ''
            );
            $recommendations[] = sprintf('Check what drove the %s increase and whether it was a one-off.', $rise['name']);
        }
        if ((float) ($context['period_change']['expenses'] ?? 0) > 0) {
            $severity = 'warning';
        }

        if ($income > 0 && $expenses > $income) {
            $severity = 'warning';
            $insights[] = sprintf('Recorded expenses (%s) exceed recorded income (%s) in this period.', $this->peso($expenses), $this->peso($income));
            $recommendations[] = 'Review non-essential categories to bring spending back under income.';
        } elseif ($income > 0 && $net / $income >= 0.2) {
            if ($severity !== 'warning') {
                $severity = 'positive';
            }
            $insights[] = sprintf('You kept %d%% of recorded income this period.', round($net / $income * 100));
            $recommendations[] = 'Consider moving part of your surplus into savings.';
        } elseif ($income <= 0 && $expenses > 0) {
            $insights[] = 'No income is recorded for this period, so spending cannot be compared with income.';
        }

        if (isset($context['income_usage'])) {
            $insights[] = 'These percentages compare recorded expenses and net cash flow with recorded income; category budget limits are tracked separately.';
        }

        return [
            'summary' => $summary,
            'insights' => $insights,
            'recommendations' => $recommendations,
            'severity' => $severity,
        ];
    }

    private function peso(float $amount): string
    {
        return '₱'.number_format($amount, 2);
    }
}
