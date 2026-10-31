<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

class AssistantFinancialContext
{
    public function __construct(private readonly ReportDataService $reports) {}

    public function forQuestion(
        User $user,
        string $question,
        array $history,
        Carbon $startDate,
        Carbon $endDate
    ): ?array {
        $previousUserMessage = collect($history)
            ->where('role', 'user')
            ->reverse()
            ->first();
        $usesPreviousContext = $this->isContextualFollowUp($question)
            && is_array($previousUserMessage)
            && is_string($previousUserMessage['content'] ?? null)
            && $this->isPersonalFinanceQuestion($previousUserMessage['content']);

        if (! $this->isPersonalFinanceQuestion($question) && ! $usesPreviousContext) {
            return null;
        }

        $contextQuestion = $usesPreviousContext
            ? $previousUserMessage['content'].' '.$question
            : $question;
        [$startDate, $endDate] = $this->requestedWindow($contextQuestion, $startDate, $endDate);
        $report = $this->reports->forRange($user, $startDate, $endDate);
        $context = [
            'selected_date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'recorded_totals' => [
                'income' => number_format($report['income'], 2, '.', ''),
                'expenses' => number_format($report['expenses'], 2, '.', ''),
                'net_cash_flow' => number_format($report['net'], 2, '.', ''),
                'transaction_count' => $report['transactionCount'],
            ],
            'expense_categories' => $report['categories']
                ->take(10)
                ->map(fn (array $category): array => [
                    'name' => $category['name'],
                    'spent' => number_format($category['spent'], 2, '.', ''),
                    'share_percent' => $category['percentage'],
                ])->values()->all(),
            'monthly_totals' => $report['monthly']
                ->slice(-12)
                ->map(fn (array $month): array => [
                    'month' => $month['label'],
                    'income' => number_format($month['income'], 2, '.', ''),
                    'expenses' => number_format($month['expenses'], 2, '.', ''),
                    'net_cash_flow' => number_format($month['net'], 2, '.', ''),
                ])->values()->all(),
        ];

        if ($this->asksAboutBudget($contextQuestion)) {
            $context['budget_status'] = $this->budgetStatus($user);
        }

        if ($this->asksAboutGoals($contextQuestion)) {
            $context['savings_goals'] = $this->savingsGoals($user, $contextQuestion);
        }

        if ($this->asksForWeekSummary($contextQuestion)) {
            $context['weekly_summary'] = $this->weeklySummary($user);
        }

        if ($this->asksAboutBalance($contextQuestion)) {
            $totals = $user->transactions()
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as income,
                     COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as expenses',
                    ['income', 'expense']
                )
                ->first();

            $allTimeIncome = round((float) $totals->income, 2);
            $allTimeExpenses = round((float) $totals->expenses, 2);
            $context['all_time_totals'] = [
                'income' => number_format($allTimeIncome, 2, '.', ''),
                'expenses' => number_format($allTimeExpenses, 2, '.', ''),
                'recorded_net_balance' => number_format($allTimeIncome - $allTimeExpenses, 2, '.', ''),
            ];
        }

        if ($this->asksForComparison($contextQuestion)) {
            $previousStart = $startDate->copy()->subMonthNoOverflow();
            $previousEnd = $endDate->copy()->subMonthNoOverflow();
            $previousReport = $this->reports->forRange($user, $previousStart, $previousEnd);
            $currentCategories = $report['categories']->keyBy('name');
            $previousCategories = $previousReport['categories']->keyBy('name');
            $categoryNames = $currentCategories->keys()
                ->merge($previousCategories->keys())
                ->unique()
                ->values();

            $context['previous_period'] = [
                'start' => $previousStart->toDateString(),
                'end' => $previousEnd->toDateString(),
                'income' => number_format($previousReport['income'], 2, '.', ''),
                'expenses' => number_format($previousReport['expenses'], 2, '.', ''),
                'net_cash_flow' => number_format($previousReport['net'], 2, '.', ''),
                'expense_categories' => $previousCategories->map(fn (array $category): array => [
                    'name' => $category['name'],
                    'spent' => number_format($category['spent'], 2, '.', ''),
                ])->values()->all(),
            ];
            $context['category_comparison'] = $categoryNames->map(function (string $name) use (
                $currentCategories,
                $previousCategories
            ): array {
                $currentSpent = (float) ($currentCategories->get($name)['spent'] ?? 0);
                $previousSpent = (float) ($previousCategories->get($name)['spent'] ?? 0);

                return [
                    'name' => $name,
                    'current_spent' => number_format($currentSpent, 2, '.', ''),
                    'previous_spent' => number_format($previousSpent, 2, '.', ''),
                    'change' => number_format($currentSpent - $previousSpent, 2, '.', ''),
                    'change_percent' => $previousSpent > 0
                        ? round((($currentSpent - $previousSpent) / $previousSpent) * 100)
                        : null,
                ];
            })->all();
            $context['period_change'] = [
                'income' => number_format($report['income'] - $previousReport['income'], 2, '.', ''),
                'expenses' => number_format($report['expenses'] - $previousReport['expenses'], 2, '.', ''),
                'net_cash_flow' => number_format($report['net'] - $previousReport['net'], 2, '.', ''),
            ];
        }

        $granularity = $this->requestedGranularity($contextQuestion);
        if ($granularity !== null) {
            $context[$granularity === 'daily' ? 'spending_by_day' : 'spending_by_week'] =
                $this->spendingSeries($user, $endDate, $startDate, $granularity);
        }

        $category = $this->mentionedCategory($user, $contextQuestion);
        if ($category !== null) {
            $spent = round((float) ($report['categories']->firstWhere('name', $category->name)['spent'] ?? 0), 2);
            $context['category_focus'] = [
                'name' => $category->name,
                'spent' => number_format($spent, 2, '.', ''),
                'share_percent' => $report['expenses'] > 0 ? round($spent / $report['expenses'] * 100) : 0,
                'transaction_count' => $user->transactions()
                    ->where('category_id', $category->id)
                    ->where('type', 'expense')
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->count(),
            ];
        }

        $simulation = $this->simulation($contextQuestion, $report, $category);
        if ($simulation !== null) {
            $context['simulation'] = $simulation;
        }

        if ($report['income'] > 0 && $this->asksAboutUtilization($contextQuestion)) {
            $context['income_usage'] = [
                'expenses_percent_of_income' => round($report['expenses'] / $report['income'] * 100),
                'savings_rate_percent' => round($report['net'] / $report['income'] * 100),
            ];
        }

        return $context;
    }

    /**
     * Lets a question widen or shift the selected range ("last six months", "this month").
     *
     * @return array{Carbon, Carbon}
     */
    private function requestedWindow(string $question, Carbon $startDate, Carbon $endDate): array
    {
        $today = now()->startOfDay();
        $words = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7,
            'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11, 'twelve' => 12];

        if (preg_match('/\b(?:last|past|previous)\s+(\d{1,2}|'.implode('|', array_keys($words)).')\s+months?\b/i', $question, $match) === 1) {
            $months = is_numeric($match[1]) ? (int) $match[1] : $words[strtolower($match[1])];
            $months = max(1, min(12, $months));

            return [$today->copy()->startOfMonth()->subMonthsNoOverflow($months - 1), $today];
        }

        if ($this->asksForWeekSummary($question)) {
            return [$today->copy()->subDays(6), $today];
        }

        if (preg_match('/\bthis month\b/i', $question) === 1) {
            return [$today->copy()->startOfMonth(), $today];
        }

        return [$startDate, $endDate];
    }

    private function requestedGranularity(string $question): ?string
    {
        if (preg_match('/\b(?:daily|day[- ]by[- ]day|per day|each day|every day)\b/i', $question) === 1) {
            return 'daily';
        }

        return preg_match('/\b(?:weekly|week[- ]by[- ]week|per week|each week)\b/i', $question) === 1 ? 'weekly' : null;
    }

    /**
     * Expense totals per day (last 62 days of the range) or per week (last 26 weeks), zero-filled.
     *
     * @return array<int, array{label: string, spent: string}>
     */
    private function spendingSeries(User $user, Carbon $endDate, Carbon $startDate, string $granularity): array
    {
        $from = $granularity === 'daily'
            ? $startDate->copy()->max($endDate->copy()->subDays(61))
            : $startDate->copy()->startOfWeek()->max($endDate->copy()->startOfWeek()->subWeeks(25));

        $totals = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$from->toDateString(), $endDate->toDateString()])
            ->selectRaw('SUBSTR(date, 1, 10) as day, SUM(amount) as spent')
            ->groupBy('day')
            ->pluck('spent', 'day');

        $buckets = [];
        for ($day = $from->copy(); $day->lte($endDate); $day->addDay()) {
            $key = $granularity === 'daily'
                ? $day->format('M j')
                : $day->copy()->startOfWeek()->format('M j');
            $buckets[$key] = ($buckets[$key] ?? 0) + (float) ($totals[$day->toDateString()] ?? 0);
        }

        return collect($buckets)
            ->map(fn (float $spent, string $label): array => [
                'label' => $granularity === 'weekly' ? 'Week of '.$label : $label,
                'spent' => number_format($spent, 2, '.', ''),
            ])->values()->all();
    }

    private function mentionedCategory(User $user, string $question): ?Category
    {
        return $user->categories()
            ->where('type', 'expense')
            ->get()
            ->filter(fn (Category $category): bool => preg_match(
                '/(?<![\pL\pN])'.preg_quote(mb_strtolower($category->name), '/').'(?![\pL\pN])/u',
                mb_strtolower($question)
            ) === 1)
            ->sortByDesc(fn (Category $category): int => mb_strlen($category->name))
            ->first();
    }

    /**
     * A hypothetical "what if I cut X by N%" projection, kept clearly separate from recorded figures.
     *
     * @return array<string, mixed>|null
     */
    private function simulation(string $question, array $report, ?Category $category): ?array
    {
        if (preg_match('/\b(?:reduce|cut|lower|decrease|trim)\b[^%]{0,60}?\bby\s+(\d{1,3}(?:\.\d+)?)\s*(?:%|percent)/i', $question, $match) !== 1) {
            return null;
        }

        $percent = (float) $match[1];
        if ($percent <= 0 || $percent > 100) {
            return null;
        }

        $appliesToAll = $category === null;
        if ($appliesToAll && preg_match('/\b(?:expenses|spending|everything|all)\b/i', $question) !== 1) {
            return null;
        }

        $actual = $appliesToAll
            ? (float) $report['expenses']
            : (float) ($report['categories']->firstWhere('name', $category->name)['spent'] ?? 0);
        $simulated = round($actual * (1 - $percent / 100), 2);
        $savings = round($actual - $simulated, 2);

        return [
            'is_hypothetical' => true,
            'note' => 'Projection only; not recorded data.',
            'applies_to' => $appliesToAll ? 'total expenses' : $category->name,
            'reduction_percent' => $percent,
            'actual_spent' => number_format($actual, 2, '.', ''),
            'simulated_spent' => number_format($simulated, 2, '.', ''),
            'projected_savings' => number_format($savings, 2, '.', ''),
            'actual_net' => number_format($report['net'], 2, '.', ''),
            'simulated_net' => number_format($report['net'] + $savings, 2, '.', ''),
        ];
    }

    private function asksAboutUtilization(string $question): bool
    {
        return (bool) preg_match('/\b(?:budgets?|savings?|saving rate|utili[sz]ation|afford|income left|how much of my income)\b/i', $question);
    }

    /**
     * Parses "if I add 100 every month/week for … in the next year" into a monthly rate and horizon.
     *
     * @return array<string, mixed>|null
     */
    private function contributionPlan(string $question): ?array
    {
        if (preg_match('/\b(?:add(?:ed|ing)?|sav(?:e|ed|ing)|put(?:ting)?|deposit(?:ed|ing)?|contribut(?:e|ed|ing)|set(?:ting)? aside|invest(?:ed|ing)?)\b[^0-9]{0,15}(?:₱|php\s*|p\s*)?(\d[\d,]*(?:\.\d+)?)(?:\s*(?:pesos?|php))?(?:\s*(?:every|each|per|a|\/)\s*(day|week|month|year)|\s+(daily|weekly|monthly|yearly))?/iu', $question, $m) !== 1) {
            return null;
        }

        $amount = (float) str_replace(',', '', $m[1]);
        if ($amount <= 0) {
            return null;
        }

        $unit = strtolower($m[2] ?? '') ?: strtolower($m[3] ?? '');
        $unit = ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month', 'yearly' => 'year'][$unit] ?? $unit;
        $assumed = $unit === '';
        if ($assumed && preg_match('/\b(?:every|each|per)\s+(day|week|month|year)\b/i', $question, $every) === 1) {
            $unit = strtolower($every[1]);
            $assumed = false;
        }
        $unit = $unit ?: 'month';
        $perMonth = $amount * ['day' => 30, 'week' => 52 / 12, 'month' => 1, 'year' => 1 / 12][$unit];

        $horizon = 12;
        $words = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7,
            'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11, 'twelve' => 12];
        if (preg_match('/\b(\d{1,2}|'.implode('|', array_keys($words)).')\s+(month|year|week)s?\b/i', $question, $h) === 1) {
            $n = is_numeric($h[1]) ? (int) $h[1] : $words[strtolower($h[1])];
            $horizon = max(1, (int) round($n * ['month' => 1, 'year' => 12, 'week' => 12 / 52][strtolower($h[2])]));
        } elseif (preg_match('/\b(?:next|this|one|a|1)\s+year\b/i', $question) === 1) {
            $horizon = 12;
        } elseif (preg_match('/\b(?:next|this)\s+month\b/i', $question) === 1) {
            $horizon = 1;
        }

        return [
            'amount' => $amount,
            'unit' => 'per '.$unit,
            'assumed_unit' => $assumed,
            'per_month' => $perMonth,
            'horizon_months' => min(120, $horizon),
        ];
    }

    private function asksAboutBudget(string $question): bool
    {
        return (bool) preg_match('/\b(?:budgets?|spending limits?|category limits?)\b/i', $question);
    }

    private function asksAboutGoals(string $question): bool
    {
        return (bool) preg_match('/\b(?:goals?|saving for|savings? target|deadline|how long (?:will|until|before|to)|when (?:will|can|would) i (?:reach|hit|finish|afford|get)|if i (?:keep|continue)|at this (?:rate|pace)|forecast|projection|project(?:ed)?|estimate)\b/i', $question);
    }

    private function asksForWeekSummary(string $question): bool
    {
        return (bool) preg_match('/\b(?:(?:1|one|a)[- ]week|weekly|week(?:ly)?[^.?!]{0,30}\b(?:report|summary|spending|expenses?)|(?:report|summary|spending|expenses?)[^.?!]{0,30}\bweek|(?:this|past|last) (?:week|7 days|seven days)|spending habits?|summary of my (?:week|spending))\b/i', $question);
    }

    /**
     * Saved goals with progress and a ranged estimate based on recent net cash flow.
     *
     * @return array<string, mixed>
     */
    private function savingsGoals(User $user, string $question = ''): array
    {
        $today = now()->startOfDay();
        $goals = $user->goals()->orderBy('created_at')->get();
        $plan = $this->contributionPlan($question);

        // Three consecutive 30-day windows give a low / typical / high saving pace.
        $windows = [];
        for ($i = 0; $i < 3; $i++) {
            $end = $today->copy()->subDays($i * 30);
            $start = $end->copy()->subDays(29);
            $totals = $user->transactions()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as income,
                     COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as expenses',
                    ['income', 'expense']
                )->first();
            $windows[] = round((float) $totals->income - (float) $totals->expenses, 2);
        }

        $low = min($windows);
        $high = max($windows);
        $typical = round(array_sum($windows) / count($windows), 2);
        $fmt = fn (float $n): string => number_format($n, 2, '.', '');
        $monthsFor = fn (float $remaining, float $pace): ?string => $pace > 0
            ? number_format($remaining / $pace, 1, '.', '')
            : null;

        $mentioned = $goals->filter(fn ($g): bool => preg_match(
            '/(?<![\pL\pN])'.preg_quote(mb_strtolower($g->name), '/').'(?![\pL\pN])/u',
            mb_strtolower($question)
        ) === 1)->pluck('id');

        $items = $goals->map(function ($goal) use ($today, $low, $high, $typical, $fmt, $monthsFor, $plan, $mentioned): array {
            $target = round((float) $goal->target, 2);
            $saved = round((float) $goal->saved, 2);
            $remaining = max(0, round($target - $saved, 2));
            $item = [
                'name' => $goal->name,
                'target' => $fmt($target),
                'saved' => $fmt($saved),
                'remaining' => $fmt($remaining),
                'progress_percent' => $target > 0 ? min(100, (int) round($saved / $target * 100)) : 0,
                'status' => $remaining <= 0 ? 'reached' : 'in_progress',
            ];

            if ($goal->deadline !== null) {
                $daysLeft = (int) $today->diffInDays($goal->deadline->copy()->startOfDay(), false);
                $item['deadline'] = $goal->deadline->toDateString();
                $item['days_left'] = $daysLeft;
                if ($remaining > 0 && $daysLeft > 0) {
                    $item['needed_per_month'] = $fmt(round($remaining / ($daysLeft / 30), 2));
                }
            }

            if ($plan !== null && ($mentioned->isEmpty() || $mentioned->contains($goal->id))) {
                $perMonth = $plan['per_month'];
                $added = round($perMonth * $plan['horizon_months'], 2);
                $finalSaved = round($saved + $added, 2);
                $item['hypothetical_contribution_plan'] = [
                    'is_hypothetical' => true,
                    'contribution_amount' => $fmt($plan['amount']),
                    'contribution_frequency' => $plan['unit'],
                    'frequency_assumed' => $plan['assumed_unit'],
                    'horizon_months' => $plan['horizon_months'],
                    'monthly_equivalent' => $fmt(round($perMonth, 2)),
                    'projected_added' => $fmt($added),
                    'projected_saved' => $fmt($finalSaved),
                    'reaches_target_in_horizon' => $finalSaved >= $target,
                    'shortfall' => $fmt(max(0, round($target - $finalSaved, 2))),
                    'months_needed' => $remaining <= 0 ? '0.0' : ($perMonth > 0 ? number_format($remaining / $perMonth, 1, '.', '') : null),
                ];
            }

            if ($remaining > 0) {
                $item['estimated_months_to_reach'] = [
                    'fastest' => $monthsFor($remaining, $high),
                    'typical' => $monthsFor($remaining, $typical),
                    'slowest' => $monthsFor($remaining, $low),
                ];
            }

            return $item;
        })->values()->all();

        return [
            'available' => $goals->isNotEmpty(),
            'goals' => $items,
            'total_target' => $fmt((float) $goals->sum('target')),
            'total_saved' => $fmt((float) $goals->sum('saved')),
            'monthly_saving_pace' => [
                'basis' => 'Recorded income minus expenses over each of the last three 30-day periods; an estimate, not a guarantee. A null value means that pace is not positive, so the goal would not be reached.',
                'lowest' => $fmt($low),
                'typical' => $fmt($typical),
                'highest' => $fmt($high),
            ],
            'projected_added_savings' => [
                'in_1_month' => ['low' => $fmt($low), 'high' => $fmt($high)],
                'in_3_months' => ['low' => $fmt($low * 3), 'high' => $fmt($high * 3)],
                'in_6_months' => ['low' => $fmt($low * 6), 'high' => $fmt($high * 6)],
            ],
        ];
    }

    /**
     * The last seven days (including today) compared with the seven days before.
     *
     * @return array<string, mixed>
     */
    private function weeklySummary(User $user): array
    {
        $today = now()->startOfDay();
        $start = $today->copy()->subDays(6);
        $previousStart = $start->copy()->subDays(7);
        $previousEnd = $start->copy()->subDay();

        $week = $this->reports->forRange($user, $start, $today);
        $previous = $this->reports->forRange($user, $previousStart, $previousEnd);
        $daily = collect($this->spendingSeries($user, $today, $start, 'daily'));
        $busiest = $daily->sortByDesc(fn (array $day): float => (float) $day['spent'])->first();
        $spent = round((float) $week['expenses'], 2);
        $previousSpent = round((float) $previous['expenses'], 2);

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $today->toDateString()],
            'income' => number_format($week['income'], 2, '.', ''),
            'expenses' => number_format($spent, 2, '.', ''),
            'net_cash_flow' => number_format($week['net'], 2, '.', ''),
            'transaction_count' => $week['transactionCount'],
            'average_daily_spend' => number_format($spent / 7, 2, '.', ''),
            'busiest_day' => $busiest !== null && (float) $busiest['spent'] > 0
                ? ['label' => $busiest['label'], 'spent' => $busiest['spent']]
                : null,
            'daily_spending' => $daily->all(),
            'top_categories' => $week['categories']->take(5)->map(fn (array $c): array => [
                'name' => $c['name'],
                'spent' => number_format($c['spent'], 2, '.', ''),
                'share_percent' => $c['percentage'],
            ])->values()->all(),
            'previous_week' => [
                'expenses' => number_format($previousSpent, 2, '.', ''),
                'change' => number_format($spent - $previousSpent, 2, '.', ''),
                'change_percent' => $previousSpent > 0 ? round(($spent - $previousSpent) / $previousSpent * 100) : null,
            ],
            'weekly_pace_if_unchanged' => [
                'estimated_month_spend' => number_format($spent / 7 * 30, 2, '.', ''),
            ],
        ];
    }

    /**
     * Current-month spending compared with the user's saved monthly category limits.
     *
     * @return array<string, mixed>
     */
    private function budgetStatus(User $user): array
    {
        $monthStart = now()->startOfMonth();
        $today = now()->startOfDay();
        $budgets = Budget::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->whereHas('category', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('type', 'expense'))
            ->get();

        $spentByCategory = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$monthStart->toDateString(), $today->toDateString()])
            ->selectRaw('category_id, SUM(amount) as spent')
            ->groupBy('category_id')
            ->pluck('spent', 'category_id');

        $categories = $budgets->map(function (Budget $budget) use ($spentByCategory): array {
            $monthlyLimit = round((float) $budget->amount, 2);
            $spent = round((float) ($spentByCategory[$budget->category_id] ?? 0), 2);
            $remaining = round($monthlyLimit - $spent, 2);
            $ratio = $monthlyLimit > 0 ? $spent / $monthlyLimit : 0;

            return [
                'name' => $budget->category->name,
                'monthly_limit' => number_format($monthlyLimit, 2, '.', ''),
                'spent' => number_format($spent, 2, '.', ''),
                'remaining' => number_format($remaining, 2, '.', ''),
                'used_percent' => round($ratio * 100),
                'status' => $remaining < 0 ? 'over' : ($ratio >= 0.75 ? 'near' : 'on_track'),
            ];
        })->values();

        $totalLimit = round((float) $budgets->sum('amount'), 2);
        $totalSpent = round((float) $categories->sum(fn (array $category): float => (float) $category['spent']), 2);

        return [
            'available' => $categories->isNotEmpty(),
            'month' => $today->format('F Y'),
            'total_monthly_limit' => number_format($totalLimit, 2, '.', ''),
            'total_spent' => number_format($totalSpent, 2, '.', ''),
            'total_remaining' => number_format($totalLimit - $totalSpent, 2, '.', ''),
            'categories' => $categories->all(),
        ];
    }

    private function isPersonalFinanceQuestion(string $question): bool
    {
        return (bool) preg_match('/\b(?:my|our)\s+(?:[\w-]+\s+){0,3}?(?:money|income|earnings?|expenses?|spending|spendings?|transactions?|budgets?|balance|savings?|categories?)\b/i', $question)
            || (bool) preg_match('/\b(?:spending|expenses?|income|transactions?)\s+(?:report|summary|recap|overview|breakdown|habits?|review)\b/i', $question)
            || (bool) preg_match('/\b(?:report|summary|recap|overview|breakdown|review)\b.{0,25}\b(?:of|on|for)\b.{0,15}\b(?:spending|expenses?|income|transactions?|week|month)\b/i', $question)
            || (bool) preg_match('/\b(?:reduce|cut|lower|decrease|trim)\b[^%]{0,50}\b(?:expenses?|spending)\b[^%]{0,20}\bby\s+\d/i', $question)
            || (bool) preg_match('/\b(?:how much did i (?:spend|earn|save)|how much have i spent|where does (?:most of )?my money go|highest spending category|spending by category|spending this month|total (?:income|expenses|spending)|transactions? this month|compare\b.{0,50}\bspending|save (?:more|money)|savings? plan|spend less|reduce (?:my )?(?:expenses|spending)|cut (?:my )?expenses|cash flow|overspending|unusual spending|recurring expenses?|net worth|available balance|do i have enough)\b/i', $question)
            || (bool) preg_match('/\bcategory\b.{0,35}\b(?:highest|most|spending|spent)\b/i', $question)
            || (bool) preg_match('/\b(?:am i|do i|can i)\b.{0,35}\b(?:budgets?|afford|debt)\b/i', $question)
            || (bool) preg_match('/\b(?:my|our)\s+(?:[\w-]+\s+){0,3}?goals?\b|\bsaving for\b|\b(?:weekly|1[- ]week|one[- ]week) (?:summary|report|recap)\b|\bspending habits?\b|\bsummary of my (?:week|spending)\b|\bif i (?:keep|continue)\b.{0,40}\b(?:saving|spending|putting|adding)\b/i', $question);
    }

    private function asksAboutBalance(string $question): bool
    {
        return (bool) preg_match('/\b(?:balance|available money|how much do i have|how much have i got|net worth|all.time|lifetime|since i started|overall totals)\b/i', $question);
    }

    private function asksForComparison(string $question): bool
    {
        return (bool) preg_match('/\b(?:compare|comparison|trend|trends|last month|previous month|increased|decreased|change over time)\b/i', $question);
    }

    private function isContextualFollowUp(string $question): bool
    {
        return (bool) preg_match(
            '/^\s*(?:what about (?:that|it|my|last month|the previous)|how about (?:that|it|my|last month|the previous)|what should i do about (?:that|it)|what could i do about (?:that|it)|how can i (?:change|reduce|adjust) (?:that|it)|what if i do (?:that|it)|can you explain (?:that|it)|could you explain (?:that|it)|tell me more|could you expand(?: on (?:that|it))?|elaborate on (?:that|it)|why (?:is|did|does) (?:that|it|the spending)|how so)\b/i',
            $question
        );
    }
}
