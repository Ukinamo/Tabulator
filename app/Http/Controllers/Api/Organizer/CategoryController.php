<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Score;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List categories for an event (event_id required in query).
     */
    public function index(Request $request)
    {
        $eventId = $request->query('event_id');
        if (! $eventId) {
            $event = Event::orderBy('event_date', 'desc')->first();
            $eventId = $event?->id;
        }
        if (! $eventId) {
            return $this->respond([], 'No event.');
        }

        $categories = Category::where('event_id', $eventId)
            ->orderBy('sort_order')
            ->withCount('criteria')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'event_id' => $c->event_id,
                'name' => $c->name,
                'weight' => (float) $c->weight,
                'description' => $c->description,
                'sort_order' => $c->sort_order,
                'criteria_count' => $c->criteria_count,
            ]);

        return $this->respond($categories->values()->all(), 'OK.');
    }

    /**
     * Create category; validate total weights ≤ 100 for event.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'name' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['integer'],
        ]);

        $currentTotal = Category::where('event_id', $data['event_id'])->sum('weight');
        $newTotal = $currentTotal + (float) $data['weight'];
        if (round($newTotal, 2) > 100) {
            return $this->error('Total category weights cannot exceed 100%.', 422);
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $category = Category::create($data);

        return $this->respond([
            'id' => $category->id,
            'event_id' => $category->event_id,
            'name' => $category->name,
            'weight' => (float) $category->weight,
            'description' => $category->description,
            'sort_order' => $category->sort_order,
        ], 'Category created.', 201);
    }

    /**
     * Show single category.
     */
    public function show(Category $category)
    {
        return $this->respond([
            'id' => $category->id,
            'event_id' => $category->event_id,
            'name' => $category->name,
            'weight' => (float) $category->weight,
            'description' => $category->description,
            'sort_order' => $category->sort_order,
        ], 'OK.');
    }

    /**
     * Update category; validate total weights ≤ 100.
     */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        if (isset($data['weight'])) {
            $currentTotal = Category::where('event_id', $category->event_id)->where('id', '!=', $category->id)->sum('weight');
            $newTotal = $currentTotal + (float) $data['weight'];
            if (round($newTotal, 2) > 100) {
                return $this->error('Total category weights cannot exceed 100%.', 422);
            }
        }

        $category->update($data);

        return $this->respond([
            'id' => $category->id,
            'event_id' => $category->event_id,
            'name' => $category->name,
            'weight' => (float) $category->weight,
            'description' => $category->description,
            'sort_order' => $category->sort_order,
        ], 'Category updated.');
    }

    /**
     * Delete category only if no criteria with submitted scores.
     */
    public function destroy(Category $category)
    {
        $criteriaIds = $category->criteria()->pluck('id');
        $hasSubmitted = Score::whereIn('criterion_id', $criteriaIds)->whereIn('status', [Score::STATUS_SUBMITTED, Score::STATUS_APPROVED])->exists();
        if ($hasSubmitted) {
            return $this->error('Cannot delete category: has criteria with submitted scores.', 422);
        }

        $category->delete();

        return $this->respond(null, 'Category deleted.', 204);
    }

    /**
     * Scoring progress for event (used by route events/{event}/progress).
     */
    public function scoringProgress(Event $event)
    {
        $service = app(\App\Services\ScoresheetService::class);

        return $this->respond($service->getProgressForEvent($event->id), 'OK.');
    }
}
