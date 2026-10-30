<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class GlobalSearchController extends Controller
{
    private const PAGES = [
        ['Dashboard', 'dashboard', 'bi-grid-1x2-fill'],
        ['Transactions', 'transactions.page', 'bi-arrow-left-right'],
        ['Categories', 'categories.index', 'bi-tag'],
        ['Budgets', 'budgets.index', 'bi-piggy-bank'],
        ['Reports', 'reports.index', 'bi-bar-chart'],
        ['Profile', 'profile.edit', 'bi-person-circle'],
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '' || mb_strlen($term) > 100) {
            return response()->json(['results' => []]);
        }

        $like = '%'.addcslashes($term, '\\%_').'%';
        $user = $request->user();
        $results = [];

        foreach (self::PAGES as [$label, $routeName, $icon]) {
            if (Route::has($routeName) && stripos($label, $term) !== false) {
                $results[] = ['group' => 'Pages', 'title' => $label, 'subtitle' => '', 'icon' => $icon, 'url' => route($routeName)];
            }
        }

        $user->categories()
            ->where('is_archived', false)
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'type'])
            ->each(function ($category) use (&$results): void {
                $results[] = [
                    'group' => 'Categories',
                    'title' => $category->name,
                    'subtitle' => ucfirst($category->type),
                    'icon' => 'bi-tag',
                    'url' => route('categories.index', ['q' => $category->name]),
                ];
            });

        $user->transactions()
            ->with('category:id,name')
            ->where(function ($query) use ($like): void {
                $query->where('description', 'like', $like)
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $like));
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->each(function ($transaction) use (&$results): void {
                $sign = $transaction->type === 'income' ? '+' : '-';
                $results[] = [
                    'group' => 'Transactions',
                    'title' => $transaction->description ?: ($transaction->category?->name ?? 'Transaction'),
                    'subtitle' => sprintf(
                        '%s · %s%s · %s',
                        $transaction->category?->name ?? 'Uncategorised',
                        $sign,
                        number_format((float) $transaction->amount, 2),
                        $transaction->date instanceof \DateTimeInterface ? $transaction->date->format('M j, Y') : $transaction->date
                    ),
                    'icon' => 'bi-arrow-left-right',
                    'url' => route('transactions.page', ['q' => $transaction->description ?: ($transaction->category?->name ?? '')]),
                ];
            });

        return response()->json(['results' => $results]);
    }
}
