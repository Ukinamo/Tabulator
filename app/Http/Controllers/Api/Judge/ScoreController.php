<?php

namespace App\Http\Controllers\Api\Judge;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScoreRequest;
use App\Models\Contestant;
use App\Models\Criterion;
use App\Models\Event;
use App\Models\Score;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function scoresheet(Request $request)
    {
        $eventId = (int) $request->query('event_id');

        /** @var \App\Models\User $judge */
        $judge = $request->user();

        $event = Event::findOrFail($eventId);

        $contestants = $event->contestants()->where('is_active', true)->orderBy('contestant_number')->get();
        $categories = $event->categories()->with('criteria')->orderBy('sort_order')->get();

        $existingScores = Score::query()
            ->where('event_id', $event->id)
            ->where('judge_id', $judge->id)
            ->get();

        return response()->json([
            'event' => $event,
            'contestants' => $contestants,
            'categories' => $categories,
            'scores' => $existingScores,
        ]);
    }

    public function store(StoreScoreRequest $request)
    {
        /** @var \App\Models\User $judge */
        $judge = $request->user();

        $validated = $request->validated();

        $criterion = Criterion::findOrFail($validated['criterion_id']);
        $contestant = Contestant::findOrFail($validated['contestant_id']);

        $score = Score::updateOrCreate(
            [
                'event_id' => $validated['event_id'],
                'judge_id' => $judge->id,
                'contestant_id' => $contestant->id,
                'criterion_id' => $criterion->id,
            ],
            [
                'score' => $validated['score'],
                'status' => Score::STATUS_DRAFT,
            ],
        );

        return response()->json(['data' => $score]);
    }

    public function update(StoreScoreRequest $request, Score $score)
    {
        /** @var \App\Models\User $judge */
        $judge = $request->user();

        abort_unless($score->judge_id === $judge->id, 403);
        abort_unless($score->status === Score::STATUS_DRAFT, 422);

        $validated = $request->validated();

        $score->update([
            'score' => $validated['score'],
        ]);

        return response()->json(['data' => $score]);
    }

    public function submitAll(Request $request)
    {
        /** @var \App\Models\User $judge */
        $judge = $request->user();

        $eventId = (int) $request->input('event_id');

        $query = Score::query()
            ->where('judge_id', $judge->id)
            ->where('status', Score::STATUS_DRAFT);

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $count = $query->update([
            'status' => Score::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'All scores submitted successfully.',
            'submitted_count' => $count,
        ]);
    }
}

