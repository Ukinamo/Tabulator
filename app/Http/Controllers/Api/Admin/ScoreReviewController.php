<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Score;
use App\Services\ScoreService;
use Illuminate\Http\Request;

class ScoreReviewController extends Controller
{
    public function __construct(
        protected ScoreService $scoreService,
    ) {
    }

    /**
     * List all submitted scores for an event (optionally filter by event_id).
     */
    public function index(Request $request)
    {
        $eventId = $request->query('event_id');
        if (! $eventId) {
            $event = \App\Models\Event::orderBy('event_date', 'desc')->first();
            $eventId = $event?->id;
        }

        if (! $eventId) {
            return $this->respond([], 'No event.');
        }

        $scores = Score::where('event_id', $eventId)
            ->whereIn('status', [Score::STATUS_SUBMITTED, Score::STATUS_APPROVED])
            ->with([
                'judge:id,name',
                'contestant' => fn ($q) => $q->withTrashed()->select('id', 'contestant_number', 'name'),
                'criterion:id,name,max_score,category_id',
            ])
            ->orderBy('judge_id')
            ->orderBy('contestant_id')
            ->orderBy('criterion_id')
            ->get()
            ->map(fn (Score $s) => [
                'id' => $s->id,
                'event_id' => $s->event_id,
                'judge_id' => $s->judge_id,
                'judge_name' => $s->judge?->name ?? 'Unknown',
                'contestant_id' => $s->contestant_id,
                'contestant_number' => $s->contestant?->contestant_number ?? '',
                'contestant_name' => $s->contestant?->name ?? 'Contestant #'.$s->contestant_id,
                'criterion_id' => $s->criterion_id,
                'criterion_name' => $s->criterion?->name ?? 'Unknown',
                'max_score' => $s->criterion?->max_score ?? 0,
                'score' => $s->score,
                'status' => $s->status,
                'submitted_at' => $s->submitted_at?->toIso8601String(),
                'approved_at' => $s->approved_at?->toIso8601String(),
            ]);

        return $this->respond($scores->values()->all(), 'OK.');
    }

    /**
     * Approve a single score; trigger recalculate.
     */
    public function approve(Request $request, Score $score)
    {
        if ($score->status === Score::STATUS_APPROVED) {
            return $this->respond([
                'id' => $score->id,
                'status' => $score->status,
            ], 'Already approved.');
        }

        $score->update([
            'status' => Score::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $this->scoreService->recalculate($score->event);

        return $this->respond([
            'id' => $score->id,
            'status' => $score->status,
        ], 'Score approved.');
    }

    /**
     * Approve all submitted scores for an event; then recalculate.
     */
    public function approveAll(Request $request, Event $event)
    {
        Score::where('event_id', $event->id)
            ->where('status', Score::STATUS_SUBMITTED)
            ->update([
                'status' => Score::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

        $this->scoreService->recalculate($event);

        return $this->respond(null, 'All scores approved.');
    }

    /**
     * Delete all submitted/approved scores for an event; then recalculate.
     */
    public function deleteAll(Request $request, Event $event)
    {
        $scores = Score::where('event_id', $event->id)
            ->whereIn('status', [Score::STATUS_SUBMITTED, Score::STATUS_APPROVED])
            ->get();

        foreach ($scores as $score) {
            $score->delete();
        }

        $this->scoreService->recalculate($event);

        return $this->respond(null, 'All scores deleted.');
    }

    /**
     * Delete (soft-delete) a single score; then recalculate event results.
     */
    public function destroy(Score $score)
    {
        $event = $score->event;
        $score->delete();
        $this->scoreService->recalculate($event);

        return $this->respond(null, 'Score deleted.', 204);
    }
}
