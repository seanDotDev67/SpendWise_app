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
            
        ])
    }
}
