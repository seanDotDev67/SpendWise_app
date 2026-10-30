<?php
// app/Http/Controllers/BudgetController.php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // last 3 months, oldest -> newest
        $months = collect([2, 1, 0])->map(function ($i) {
            $d = now()->startOfMonth()->subMonths($i);
            return [
                'key' => $d->format('Y-m'),
                'label' => $d->format('F Y'),
                'days' => $d->daysInMonth,
                'day' => $i === 0 ? now()->day : $d->daysInMonth,
                'cur' => $i === 0,
            ];
        })->values();

        $from = Carbon::parse($months->first()['key'] . '-01')->startOfMonth();

        // ⚠ Adjust the column names to match YOUR transactions table
        // (assumed: user_id, category_id, type, amount, date)
        $spent = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('date', '>=', $from)
            ->get()
            ->groupBy('category_id')
            ->map(fn($rows) => $rows
                ->groupBy(fn($t) => Carbon::parse($t->date)->format('Y-m'))
                ->map(fn($g) => (float) $g->sum('amount')));

        $budgets = Budget::where('user_id', $user->id)->pluck('amount', 'category_id');

        $categories = Category::where('user_id', $user->id)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'color' => $c->color,
                'limit' => $budgets->has($c->id) ? (float) $budgets[$c->id] : null,
                'spent' => $spent->get($c->id, collect())->all(),
            ])->values();

        $goals = Goal::where('user_id', $user->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'icon' => $g->icon,
                'target' => (float) $g->target,
                'saved' => (float) $g->saved,
                'deadline' => optional($g->deadline)->format('Y-m-d'),
            ])->values();

        return view('budgets.index', compact('categories', 'months', 'goals'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:100',
        ]);

        Category::where('id', $data['category_id'])->where('user_id', $request->user()->id)->firstOrFail();

        Budget::updateOrCreate(
            ['user_id' => $request->user()->id, 'category_id' => $data['category_id']],
            ['amount' => $data['amount']]
        );

        return redirect()->route('budgets.index')->with('status', 'Budget saved.');
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        $data = $request->validate(['amount' => 'required|numeric|min:100']);

        Budget::updateOrCreate(
            ['user_id' => $request->user()->id, 'category_id' => $category->id],
            ['amount' => $data['amount']]
        );

        return redirect()->route('budgets.index')->with('status', 'Budget updated.');
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);

        Budget::where('user_id', $request->user()->id)->where('category_id', $category->id)->delete();

        return redirect()->route('budgets.index')->with('status', 'Budget removed.');
    }
}