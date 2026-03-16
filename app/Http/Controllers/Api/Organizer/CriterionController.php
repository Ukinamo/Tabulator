<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Criterion;
use App\Models\Score;
use Illuminate\Http\Request;

class CriterionController extends Controller
{
    /**
     * List criteria for a category.
     */
    public function index(Category $category)
    {
        $criteria = $category->criteria()->orderBy('sort_order')->get()->map(fn (Criterion $c) => [
            'id' => $c->id,
            'category_id' => $c->category_id,
            'name' => $c->name,
            'max_score' => (float) $c->max_score,
            'description' => $c->description,
            'sort_order' => $c->sort_order,
        ]);

        return $this->respond($criteria->values()->all(), 'OK.');
    }

    /**
     * Create criterion.
     */
    public function store(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['integer'],
        ]);

        $data['category_id'] = $category->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $criterion = Criterion::create($data);

        return $this->respond([
            'id' => $criterion->id,
            'category_id' => $criterion->category_id,
            'name' => $criterion->name,
            'max_score' => (float) $criterion->max_score,
            'description' => $criterion->description,
            'sort_order' => $criterion->sort_order,
        ], 'Criterion created.', 201);
    }

    /**
     * Show single criterion.
     */
    public function show(Category $category, Criterion $criterion)
    {
        if ($criterion->category_id !== $category->id) {
            return $this->error('Criterion not found for this category.', 404);
        }

        return $this->respond([
            'id' => $criterion->id,
            'category_id' => $criterion->category_id,
            'name' => $criterion->name,
            'max_score' => (float) $criterion->max_score,
            'description' => $criterion->description,
            'sort_order' => $criterion->sort_order,
        ], 'OK.');
    }

    /**
     * Update criterion.
     */
    public function update(Request $request, Category $category, Criterion $criterion)
    {
        if ($criterion->category_id !== $category->id) {
            return $this->error('Criterion not found for this category.', 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'max_score' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $criterion->update($data);

        return $this->respond([
            'id' => $criterion->id,
            'category_id' => $criterion->category_id,
            'name' => $criterion->name,
            'max_score' => (float) $criterion->max_score,
            'description' => $criterion->description,
            'sort_order' => $criterion->sort_order,
        ], 'Criterion updated.');
    }

    /**
     * Delete criterion only if no submitted scores.
     */
    public function destroy(Category $category, Criterion $criterion)
    {
        if ($criterion->category_id !== $category->id) {
            return $this->error('Criterion not found for this category.', 404);
        }

        $hasScores = Score::where('criterion_id', $criterion->id)->exists();
        if ($hasScores) {
            return $this->error('Cannot delete criterion: has scores submitted.', 422);
        }

        $criterion->delete();

        return $this->respond(null, 'Criterion deleted.', 204);
    }
}
