<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\SpendingHabitAnalyzer;
use App\Support\CategoryColor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __construct(private readonly SpendingHabitAnalyzer $habits) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $now = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $allTimeTotals = Transaction::query()
            ->where('user_id', $user->id)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as income,
                 COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as expenses',
                ['income', 'expense']
            )
            ->first();

        $monthlyTotals = Transaction::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as income,
                 COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as expenses',
                ['income', 'expense']
            )
            ->first();

        $income = round((float) $monthlyTotals->income, 2);
        $expenses = round((float) $monthlyTotals->expenses, 2);
        $monthlyNet = round($income - $expenses, 2);
        $balance = round((float) $allTimeTotals->income - (float) $allTimeTotals->expenses, 2);
        $incomeSpentPercentage = $income > 0 ? round(($expenses / $income) * 100) : 0;

        $fallbackColors = ['#2F4A3A', '#7F9468', '#D98E96', '#F2C4C9', '#B8A98A'];
        $spendingCategories = Transaction::query()
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$monthStart, $monthEnd])
            ->select('categories.id', 'categories.name', 'categories.color')
            ->selectRaw('SUM(transactions.amount) as spent')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('spent')
            ->get()
            ->map(function ($category, int $index) use ($expenses, $fallbackColors): array {
                $spent = round((float) $category->spent, 2);
                $color = CategoryColor::resolve(
                    $category->color,
                    $fallbackColors[$index % count($fallbackColors)]
                );

                return [
                    'name' => $category->name,
                    'spent' => $spent,
                    'color' => $color,
                    'percentage' => $expenses > 0 ? round(($spent / $expenses) * 100) : 0,
                ];
            });

        $totalSpent = $expenses;
        $donut = $this->donutGradient($spendingCategories);
        $coachInsight = $this->coachInsight($income, $expenses, $monthlyNet, $spendingCategories);
        $recentTransactions = Transaction::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'userName' => $user->name,
            'firstName' => explode(' ', trim($user->name))[0],
            'initials' => collect(explode(' ', trim($user->name)))
                ->filter()
                ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
                ->take(2)
                ->implode(''),
            'now' => $now,
            'balance' => $balance,
            'income' => $income,
            'expenses' => $expenses,
            'monthlyNet' => $monthlyNet,
            'incomeSpentPercentage' => $incomeSpentPercentage,
            'totalSpent' => $totalSpent,
            'spendingCategories' => $spendingCategories,
            'donut' => $donut,
            'coachInsight' => $coachInsight,
            'habits' => $this->habits->analyze($user, $now),
            'categories' => $user->categories()
                ->where('is_archived', false)
                ->orderBy('name')
                ->get(),
            'recentTransactions' => $recentTransactions,
        ]);
    }

    private function coachInsight(float $income, float $expenses, float $monthlyNet, Collection $categories): array
    {
        $topCategory = $categories->first();

        if ($income <= 0 && $expenses <= 0) {
            return [
                'title' => 'Let’s build a clear picture together',
                'message' => 'There isn’t any income or spending recorded for this month yet. Add transactions as they happen and I can help you spot a realistic place to save—without guessing at your finances.',
                'category' => null,
            ];
        }

        if ($income <= 0) {
            return [
                'title' => 'Let’s get a fuller picture',
                'message' => 'You’ve recorded expenses but no income this month, so I can’t tell whether your spending is affordable. If you’re looking to save, review flexible costs first and keep essentials a priority.',
                'category' => $topCategory,
            ];
        }

        if ($expenses <= 0) {
            return [
                'title' => 'A helpful start to the month',
                'message' => 'Income is recorded, but there are no expenses logged yet. Record your regular costs too, then we can choose a savings step that fits what you actually have left after essentials.',
                'category' => null,
            ];
        }

        if ($monthlyNet < 0) {
            return [
                'title' => 'Spending is ahead of recorded income',
                'message' => 'Your recorded expenses are ₱'.number_format(abs($monthlyNet), 2).' more than your recorded income so far. This may be a timing issue or missing income. If your records are complete, consider reviewing flexible spending in your largest category before making cuts to essentials.',
                'category' => $topCategory,
            ];
        }

        if ($monthlyNet == 0.0) {
            return [
                'title' => 'You’re breaking even so far',
                'message' => 'Your recorded income and expenses currently balance. There isn’t a recorded surplus to set aside yet. You could review flexible purchases in your largest category for one small change, while keeping essentials covered.',
                'category' => $topCategory,
            ];
        }

        return [
            'title' => 'There may be room to save',
            'message' => 'You have ₱'.number_format($monthlyNet, 2).' more recorded income than expenses so far this month. If your essential costs and upcoming bills are included, consider setting aside a comfortable portion—even a small, sustainable amount is a good start.',
            'category' => $topCategory,
        ];
    }

    private function donutGradient(Collection $categories): string
    {
        $total = $categories->sum('spent');

        if ($total <= 0) {
            return 'conic-gradient(#DAD8D2 0% 100%)';
        }

        $position = 0;
        $stops = [];

        foreach ($categories as $category) {
            $start = $position;
            $position += ($category['spent'] / $total) * 100;
            $stops[] = "{$category['color']} {$start}% {$position}%";
        }

        return 'conic-gradient('.implode(', ', $stops).')';
    }
}
