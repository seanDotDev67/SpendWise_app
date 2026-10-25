<?php

namespace App\Http\Controllers;

use App\Model\App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(): JsonResponse{
        return response()->json(
            auth()->user()->transactions()
            ->with('category')
            ->ordebyDesc('date')
            ->get()
        );
    }
    public function store(Request $request): JsonResponse{
       $validated = $request->validate([
            'category_id' => [
                'required', 'integer',
                // THE ownership check: category must exist AND belong to YOU
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
            ],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'type'        => ['required', Rule::in(['income', 'expense'])],
            'description' => ['nullable', 'string', 'max:255'],
            'date'        => ['required', 'date'],
        ]);

        // user_id set BY the relationship — never from the request
        $transaction = auth()->user()->transactions()->create($validated);

        return response()->json($transaction->load('category'), 201);
    }
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless((int) $transaction->user_id === (int) auth()->id(), 404);

        $validated = $request->validate([
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where('user_id', auth()->id()),
            ],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'type'        => ['required', Rule::in(['income', 'expense'])],
            'description' => ['nullable', 'string', 'max:255'],
            'date'        => ['required', 'date'],
        ]);

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
