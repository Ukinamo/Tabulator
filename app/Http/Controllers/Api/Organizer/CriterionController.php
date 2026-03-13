<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Criterion;
use Illuminate\Http\Request;

class CriterionController extends Controller
{
    public function index(Category $category)
    {
        return response()->json([
            'data' => $category->criteria()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $criterion = $category->criteria()->create($data);

        return response()->json(['data' => $criterion], 201);
    }

    public function show(Category $category, Criterion $criterion)
    {
        abort_unless($criterion->category_id === $category->id, 404);

        return response()->json(['data' => $criterion]);
    }

    public function update(Request $request, Category $category, Criterion $criterion)
    {
        abort_unless($criterion->category_id === $category->id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'max_score' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
        ]);

        $criterion->update($data);

        return response()->json(['data' => $criterion]);
    }

    public function destroy(Category $category, Criterion $criterion)
    {
        abort_unless($criterion->category_id === $category->id, 404);

        $criterion->delete();

        return response()->json([], 204);
    }
}

