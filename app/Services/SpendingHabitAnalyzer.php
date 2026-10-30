<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

/**
 * Finds the user's strongest spending pattern from their own recorded transactions.
 * Everything is recomputed from the database on each request; nothing is stored or trained.
 */
class SpendingHabitAnalyzer
{
    private const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    private const WEEKDAY_NAMES = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    private const LOOKBACK_DAYS = 90;

    private const MIN_WEEKDAY_TRANSACTIONS = 12;

    /**
     * @return array{lead: ?array<string, mixed>, signals: array<int, array<string, mixed>>}
     */
    public function analyze(User $user, Carbon $today): array
    {
        $today = $today->copy()->startOfDay();
        $signals = array_values(array_filter([
            $this->paceSignal($user, $today),
            $this->categoryShiftSignal($user, $today),
            $this->weekdaySignal($user, $today),
            $this->consistencySignal($user, $today),
        ]));

        usort($signals, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return ['lead' => $signals[0] ?? null, 'signals' => $signals];
    }

    /** Spending so far this month compared with the same number of days last month. */
    private function paceSignal(User $user, Carbon $today): ?array
    {
        $dayCount = $today->day;
        $thisStart = $today->copy()->startOfMonth();
        $lastStart = $thisStart->copy()->subMonthNoOverflow();
        $lastEnd = $lastStart->copy()->addDays($dayCount - 1)->min($lastStart->copy()->endOfMonth());

        $current = $this->expenseTotal($user, $thisStart, $today);
        $previous = $this->expenseTotal($user, $lastStart, $lastEnd);
        if ($previous <= 0 || $current <= 0) {
            return null;
        }

        $change = round((($current - $previous) / $previous) * 100);
        if (abs($change) < 15) {
            return null;
        }

        $higher = $change > 0;

        return [
            'type' => 'pace',
            'priority' => $higher ? 1 : 4,
            'tone' => $higher ? 'warning' : 'positive',
            'title' => $higher ? 'You’re spending faster than last month' : 'You’re spending less than last month',
            'message' => 'By day '.$dayCount.', you have spent ₱'.number_format($current, 2).' compared with ₱'.number_format($previous, 2).' over the same days last month, '.($higher ? 'up ' : 'down ').abs($change).'%.',
            'chart' => [
                'kind' => 'compare',
                'rows' => [
                    ['label' => 'Last month', 'value' => $previous],
                    ['label' => 'This month', 'value' => $current],
                ],
            ],
        ];
    }

    /** The category whose spending changed most against the same days last month. */
    private function categoryShiftSignal(User $user, Carbon $today): ?array
    {
        $dayCount = $today->day;
        $thisStart = $today->copy()->startOfMonth();
        $lastStart = $thisStart->copy()->subMonthNoOverflow();
        $lastEnd = $lastStart->copy()->addDays($dayCount - 1)->min($lastStart->copy()->endOfMonth());

        $current = $this->categoryTotals($user, $thisStart, $today);
        $previous = $this->categoryTotals($user, $lastStart, $lastEnd);

        $best = null;
        foreach ($current as $name => $amount) {
            $before = $previous[$name] ?? 0.0;
            $difference = $amount - $before;
            if ($before <= 0 || $difference < 100 || ($difference / $before) < 0.25) {
                continue;
            }
            if ($best === null || $difference > $best['difference']) {
                $best = ['name' => $name, 'amount' => $amount, 'before' => $before, 'difference' => $difference];
            }
        }

        if ($best === null) {
            return null;
        }

        $percent = round(($best['difference'] / $best['before']) * 100);

        return [
            'type' => 'category_shift',
            'priority' => 2,
            'tone' => 'warning',
            'title' => $best['name'].' is climbing',
            'message' => $best['name'].' spending is ₱'.number_format($best['amount'], 2).' so far, up '.$percent.'% from ₱'.number_format($best['before'], 2).' over the same days last month.',
            'chart' => [
                'kind' => 'compare',
                'rows' => [
                    ['label' => 'Last month', 'value' => $best['before']],
                    ['label' => 'This month', 'value' => $best['amount']],
                ],
            ],
        ];
    }

    /** Which weekday carries a disproportionate share of spending over the last 90 days. */
    private function weekdaySignal(User $user, Carbon $today): ?array
    {
        $rows = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$today->copy()->subDays(self::LOOKBACK_DAYS - 1)->toDateString(), $today->toDateString()])
            ->toBase()
            ->get(['date', 'amount']);

        if ($rows->count() < self::MIN_WEEKDAY_TRANSACTIONS) {
            return null;
        }

        $totals = array_fill(0, 7, 0.0);
        foreach ($rows as $row) {
            $totals[Carbon::parse((string) $row->date)->dayOfWeekIso - 1] += (float) $row->amount;
        }

        $sum = array_sum($totals);
        if ($sum <= 0) {
            return null;
        }

        $topIndex = array_search(max($totals), $totals, true);
        $share = round(($totals[$topIndex] / $sum) * 100);
        if ($share < 25) {
            return null;
        }

        $max = max($totals);

        return [
            'type' => 'weekday',
            'priority' => 3,
            'tone' => 'neutral',
            'title' => self::WEEKDAY_NAMES[$topIndex].' is your biggest spending day',
            'message' => $share.'% of your spending over the last '.self::LOOKBACK_DAYS.' days landed on '.self::WEEKDAY_NAMES[$topIndex].'s. Planning ahead for that day is the easiest place to make a difference.',
            'chart' => [
                'kind' => 'bars',
                'rows' => array_map(fn (int $i): array => [
                    'label' => self::WEEKDAYS[$i],
                    'value' => round($totals[$i], 2),
                    'percent' => $max > 0 ? round(($totals[$i] / $max) * 100) : 0,
                    'highlight' => $i === $topIndex,
                ], range(0, 6)),
            ],
        ];
    }

    /** How regularly the user records transactions; sparse logging makes every insight less reliable. */
    private function consistencySignal(User $user, Carbon $today): ?array
    {
        $first = Transaction::query()->where('user_id', $user->id)->min('date');
        if ($first === null || Carbon::parse((string) $first)->greaterThan($today->copy()->subDays(13))) {
            return null;
        }

        $days = Transaction::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$today->copy()->subDays(13)->toDateString(), $today->toDateString()])
            ->toBase()
            ->pluck('date')
            ->map(fn ($date): string => Carbon::parse((string) $date)->toDateString())
            ->unique()
            ->count();

        if ($days > 3) {
            return null;
        }

        return [
            'type' => 'consistency',
            'priority' => 5,
            'tone' => 'neutral',
            'title' => 'Your records have gaps lately',
            'message' => 'You recorded transactions on only '.$days.' of the last 14 days. Logging as things happen makes your totals and patterns more trustworthy.',
            'chart' => ['kind' => 'dots', 'filled' => $days, 'total' => 14],
        ];
    }

    private function expenseTotal(User $user, Carbon $from, Carbon $to): float
    {
        return round((float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount'), 2);
    }

    /**
     * @return array<string, float>
     */
    private function categoryTotals(User $user, Carbon $from, Carbon $to): array
    {
        return Transaction::query()
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('categories.name')
            ->selectRaw('categories.name as name, SUM(transactions.amount) as total')
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->name => round((float) $row->total, 2)])
            ->all();
    }
}
