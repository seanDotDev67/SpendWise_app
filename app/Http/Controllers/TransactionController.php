<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function page(Request $request): View
    {
        $user = $request->user();
        $transactions = $user->transactions()
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $balance = $user->transactions()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount WHEN type = ? THEN -amount ELSE 0 END), 0) as balance',
                ['income', 'expense']
            )
            ->value('balance');

        return view('transactions.index', [
            'user' => $user,
            'categories' => $user->categories()
                ->where('is_archived', false)
                ->orderBy('name')
                ->get(),
            'transactions' => $transactions,
            'balance' => (float) $balance,
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json(
            auth()->user()->transactions()
                ->with('category')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'description' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $category = $request->user()->categories()->findOrFail($validated['category_id']);

        if ($category->type !== $validated['type']) {
            throw ValidationException::withMessages([
                'category_id' => ['The selected category does not match the transaction type.'],
            ]);
        }

        $transaction = $request->user()->transactions()->create($validated);

        return response()->json($transaction->load('category'), 201);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless((int) $transaction->user_id === (int) auth()->id(), 404);

        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'description' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $category = $request->user()->categories()->findOrFail($validated['category_id']);

        if ($category->type !== $validated['type']) {
            throw ValidationException::withMessages([
                'category_id' => ['The selected category does not match the transaction type.'],
            ]);
        }

        $transaction->update($validated);

        return response()->json($transaction->load('category'));
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        abort_unless((int) $transaction->user_id === (int) auth()->id(), 404);

        $transaction->delete();

        return response()->json(null, 204);
    }
}
