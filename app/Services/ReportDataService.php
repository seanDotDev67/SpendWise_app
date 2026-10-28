<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ReportDataService
{
    private const CATEGORY_COLORS = [
        '#2F4A3A',
        '#7F9468',
        '#D98E96',
        '#F2C4C9',
        '#B8A98A',
        '#6687A8',
        '#C49A54',
        '#7E6A9B',
    ];

    public function forRange(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $transactions = $user->transactions()->whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ]);

        $totals = (clone $transactions)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as income,
                 COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as expenses,
                 COUNT(*) as transaction_count',
                ['income', 'expense']
            )
            ->first();

        $income = round((float) $totals->income, 2);
        $expenses = round((float) $totals->expenses, 2);

        $categoryRows = (clone $transactions)
            ->join('categories', function ($join): void {
                $join->on('categories.id', '=', 'transactions.category_id')
                    ->on('categories.user_id', '=', 'transactions.user_id');
            })
            ->where('transactions.type', 'expense')
            ->select('categories.name')
            ->selectRaw('SUM(transactions.amount) as spent')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('spent')
            ->get();

        $categories = $categoryRows->values()->map(
            fn ($category, int $index): array => [
                'name' => $category->name,
                'spent' => round((float) $category->spent, 2),
                'percentage' => $expenses > 0
                    ? round(((float) $category->spent / $expenses) * 100)
                    : 0,
                'color' => self::CATEGORY_COLORS[$index % count(self::CATEGORY_COLORS)],
            ]
        );

        $monthlyRows = (clone $transactions)
            ->selectRaw(
                "SUBSTR(date, 1, 7) as month,
                 COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                 COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expenses"
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthly = collect(CarbonPeriod::create(
            $startDate->copy()->startOfMonth(),
            '1 month',
            $endDate->copy()->startOfMonth()
        ))->map(function (Carbon $month) use ($monthlyRows): array {
            $key = $month->format('Y-m');
            $row = $monthlyRows->get($key);
            $monthIncome = round((float) ($row->income ?? 0), 2);
            $monthExpenses = round((float) ($row->expenses ?? 0), 2);

            return [
                'key' => $key,
                'label' => $month->format('M Y'),
                'income' => $monthIncome,
                'expenses' => $monthExpenses,
                'net' => round($monthIncome - $monthExpenses, 2),
            ];
        });

        $recentTransactions = (clone $transactions)
            ->with('category:id,name,type')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'income' => $income,
            'expenses' => $expenses,
            'net' => round($income - $expenses, 2),
            'transactionCount' => (int) $totals->transaction_count,
            'categories' => $categories,
            'topCategories' => $categories->take(5),
            'monthly' => $monthly,
            'chartPoints' => $this->chartPoints($monthly),
            'incomeExpenseBars' => $this->incomeExpenseBars($monthly),
            'categoryGradient' => $this->categoryGradient($categories),
            'recentTransactions' => $recentTransactions,
        ];
    }

    private function incomeExpenseBars(Collection $monthly): array
    {
        $maximum = max(
            1,
            (float) $monthly->max('income'),
            (float) $monthly->max('expenses')
        );

        return $monthly->map(fn (array $month): array => [
            ...$month,
            'incomeHeight' => $month['income'] > 0
                ? max(4, round(($month['income'] / $maximum) * 100))
                : 0,
            'expenseHeight' => $month['expenses'] > 0
                ? max(4, round(($month['expenses'] / $maximum) * 100))
                : 0,
        ])->all();
    }

    private function chartPoints(Collection $monthly): array
    {
        $values = $monthly->flatMap(fn (array $month): array => [
            $month['income'],
            $month['expenses'],
            $month['net'],
        ]);
        $minimum = (float) $values->min();
        $maximum = (float) $values->max();
        $spread = max(1, $maximum - $minimum);
        $lastIndex = max(1, $monthly->count() - 1);

        $points = [];
        foreach (['income', 'expenses', 'net'] as $metric) {
            $points[$metric] = $monthly->values()->map(function (array $month, int $index) use (
                $metric,
                $minimum,
                $spread,
                $lastIndex
            ): string {
                $x = ($index / $lastIndex) * 100;
                $y = 90 - (($month[$metric] - $minimum) / $spread) * 80;

                return round($x, 2).','.round($y, 2);
            })->implode(' ');
        }

        return $points;
    }

    private function categoryGradient(Collection $categories): string
    {
        $total = (float) $categories->sum('spent');
        if ($total <= 0) {
            return 'conic-gradient(#DAD8D2 0% 100%)';
        }

        $position = 0;
        $stops = [];
        foreach ($categories as $category) {
            $next = $position + ($category['spent'] / $total) * 100;
            $stops[] = "{$category['color']} {$position}% {$next}%";
            $position = $next;
        }

        return 'conic-gradient('.implode(', ', $stops).')';
    }
}
