<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
   
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
        ]);

        $category = Category::find($validated['category_id']);

        if ($category && $category->type !== $validated['type']) {
            throw ValidationException::withMessages([
                'category_id' => ['The selected category does not match the transaction type.'],
            ]);
        }

        $transaction = auth()->user()->transactions()->create($validated);

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
        ]);

        $category = Category::find($validated['category_id']);

        if ($category && $category->type !== $validated['type']) {
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
