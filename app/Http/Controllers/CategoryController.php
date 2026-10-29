<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        // same JSON as before for API calls and tests
        if ($request->wantsJson()) {
            return response()->json(
                auth()->user()->categories()->orderBy('name')->get()
            );
        }

        // opening /categories in the browser shows the page
        return view('categories.index', $this->pageData());
    }

public function store(Request $request): JsonResponse|RedirectResponse
{
    $validated = $request->validate([
        'name' => [
            'required', 'string', 'max:255',
            Rule::unique('categories', 'name')->where(
                fn($q) => $q->where('user_id', auth()->id())->where('type', $request->input('type'))
            ),
        ],
        'type'  => ['required', 'in:income,expense'],
        'color' => ['nullable', 'string', 'max:7'],
        'icon'  => ['nullable', 'string', 'max:255'],
        'is_archived' => ['sometimes', 'boolean'],
    ]);

    $category = new Category();
    $category->user_id = auth()->id();
    $category->name    = $validated['name'];
    $category->type    = $validated['type'];
    $category->color   = $validated['color'] ?? '#3b4a3c';
    $category->icon    = $validated['icon'] ?? 'dots';
    $category->save();

    if ($request->wantsJson()) {
        return response()->json($category, 201);
    }

    return redirect()->route('categories.index')
        ->with('status', 'Category added.')
        ->with('selected_id', $category->id);
}

public function update(Request $request, Category $category): JsonResponse|RedirectResponse
{
    abort_unless((int) $category->user_id === (int) auth()->id(), 404);

    $validated = $request->validate([
        'name' => [
            'required', 'string', 'max:255',
            Rule::unique('categories', 'name')->ignore($category->id)->where(
                fn($q) => $q->where('user_id', auth()->id())->where('type', $request->input('type'))
            ),
        ],
        'type'  => ['required', 'in:income,expense'],
        'color' => ['nullable', 'string', 'max:7'],
        'icon'  => ['nullable', 'string', 'max:255'],
        'is_archived' => ['sometimes', 'boolean'],
    ]);

    $category->update($validated);

    if ($request->wantsJson()) {
        return response()->json($category);
    }

    return redirect()->route('categories.index')
        ->with('status', 'Category updated.')
        ->with('selected_id', $category->id);
}

public function destroy(Request $request, Category $category): JsonResponse|RedirectResponse
{
    abort_unless((int) $category->user_id === (int) auth()->id(), 404);

    if ($category->transactions()->exists()) {
        $message = $category->name . ' still has transactions, so it can’t be deleted yet.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 422);
        }
        return redirect()->route('categories.index')->withErrors(['category' => $message]);
    }

    $category->delete();

    if ($request->wantsJson()) {
        return response()->json(null, 204);
    }

    return redirect()->route('categories.index')->with('status', 'Category deleted.');
}

    // ===== data for the Blade page =====
    // ===== data for the Blade page =====
    private function pageData(): array
    {
        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i));

        $categories = auth()->user()->categories()->active()->orderBy('name')->get();

        [$byCategory, $used] = $this->transactionStats($categories->pluck('id'));

        $list = $categories->map(function ($c) use ($byCategory, $used, $months) {
            $rows = $byCategory->get($c->id, collect());
            $month = $rows->filter(fn($t) => $t['date']->isSameMonth(now(), true));

            return [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type,
                'color' => $c->color ?: '#3b4a3c',
                'icon' => $c->icon ?: 'dots',
                'count' => $month->count(),
                'total' => (float) $month->sum('amount'),
                'locked' => $used->contains($c->id),
                'history' => $months->take(5)->map(
                    fn($m) => (float) $rows->filter(fn($t) => $t['date']->isSameMonth($m, true))->sum('amount')
                )->values(),
                'recent' => $rows->take(3)->map(fn($t) => [
                    'date' => $t['date']->format('M j'),
                    'description' => $t['description'],
                    'amount' => $t['amount'],
                ])->values(),
            ];
        })->values();

        return [
            'categories' => $list,
            'months' => $months->map->format('M')->all(),
        ];
    }

    private function transactionStats($ids): array
    {
        $model = \App\Models\Transaction::class;

        // no transactions model yet, or no categories yet: the page just shows zeros
        if (!class_exists($model) || $ids->isEmpty()) {
            return [collect(), collect()];
        }

        $used = $model::where('user_id', auth()->id())
            ->whereIn('category_id', $ids)
            ->distinct()->pluck('category_id');

        $byCategory = $model::where('user_id', auth()->id())
            ->whereIn('category_id', $ids)
            ->where('date', '>=', now()->subMonths(5)->startOfMonth())
            ->orderByDesc('date')
            ->get()
            ->map(fn($t) => [
                'category_id' => $t->category_id,
                'date' => Carbon::parse($t->date),
                'amount' => (float) $t->amount,
                'description' => (string) $t->description,
            ])->groupBy('category_id');

        return [$byCategory, $used];
    }
}