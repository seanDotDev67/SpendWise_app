<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
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

                return [
                    'name' => $category->name,
                    'spent' => $spent,
                    'color' => $category->color ?: $fallbackColors[$index % count($fallbackColors)],
                    'percentage' => $expenses > 0 ? round(($spent / $expenses) * 100) : 0,
                ];
            });

        $totalSpent = $expenses;
        $donut = $this->donutGradient($spendingCategories);
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
            'recentTransactions' => $recentTransactions,
        ]);
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
