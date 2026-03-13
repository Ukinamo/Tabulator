<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $eventId = $request->query('event_id');

        $query = Category::query()->with('event');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        return response()->json([
            'data' => $query->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'name' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category = Category::create($data);

        return response()->json(['data' => $category], 201);
    }

    public function show(Category $category)
    {
        return response()->json(['data' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
        ]);

        $category->update($data);

        return response()->json(['data' => $category]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([], 204);
    }

    public function scoringProgress(Event $event)
    {
        // Simple placeholder: counts judges and submitted scores.
        $totalScores = $event->scores()->count();
        $submittedScores = $event->scores()->where('status', 'submitted')->count();

        return response()->json([
            'event_id' => $event->id,
            'total_scores' => $totalScores,
            'submitted_scores' => $submittedScores,
        ]);
    }
}

