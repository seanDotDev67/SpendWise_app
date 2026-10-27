<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            auth()->user()->categories()->orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(fn ($query) => $query->where('user_id', auth()->id())->where('type', $request->input('type'))),
            ],
            'type' => ['required', 'in:income,expense'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_archived' => ['sometimes', 'boolean'],
        ]);

        $category = auth()->user()->categories()->create($validated);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        abort_unless((int) $category->user_id === (int) auth()->id(), 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id)->where(fn ($query) => $query->where('user_id', auth()->id())->where('type', $request->input('type'))),
            ],
            'type' => ['required', 'in:income,expense'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_archived' => ['sometimes', 'boolean'],
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        abort_unless((int) $category->user_id === (int) auth()->id(), 404);

        $category->delete();

        return response()->json(null, 204);
    }
}
