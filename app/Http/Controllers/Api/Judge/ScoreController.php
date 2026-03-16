<?php

namespace App\Http\Controllers\Api\Judge;

use App\Http\Controllers\Controller;
use App\Models\Contestant;
use App\Models\Criterion;
use App\Models\Event;
use App\Models\Score;
use App\Services\ScoresheetService;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function __construct(
        protected ScoresheetService $scoresheetService,
    ) {
    }

    /**
     * Structured scoresheet matrix for this judge and event.
     */
    public function scoresheet(Event $event)
    {
        $matrix = $this->scoresheetService->getScoresheetForJudge($this->guard()->id(), $event->id);

        return $this->respond($matrix, 'OK.');
    }

    /**
     * Save or upsert a single score (draft).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'contestant_id' => ['required', 'exists:contestants,id'],
            'criterion_id' => ['required', 'exists:criteria,id'],
            'score' => ['required', 'numeric', 'min:0'],
        ]);

        $criterion = Criterion::findOrFail($data['criterion_id']);
        if ((float) $data['score'] > (float) $criterion->max_score) {
            return $this->error('Score cannot exceed criterion max score.', 422);
        }

        $contestant = Contestant::findOrFail($data['contestant_id']);
        if ($contestant->event_id != $data['event_id']) {
            return $this->error('Contestant does not belong to this event.', 422);
        }

        if ($criterion->category->event_id != $data['event_id']) {
            return $this->error('Criterion does not belong to this event.', 422);
        }

        $judgeId = $request->user()->id;
        $score = Score::updateOrCreate(
            [
                'judge_id' => $judgeId,
                'contestant_id' => $data['contestant_id'],
                'criterion_id' => $data['criterion_id'],
            ],
            [
                'event_id' => $data['event_id'],
                'score' => $data['score'],
                'status' => Score::STATUS_DRAFT,
            ]
        );

        return $this->respond([
            'id' => $score->id,
            'event_id' => $score->event_id,
            'contestant_id' => $score->contestant_id,
            'criterion_id' => $score->criterion_id,
            'score' => $score->score,
            'status' => $score->status,
        ], 'Score saved.', 201);
    }

    /**
     * Update draft score. 403 if already submitted.
     */
    public function update(Request $request, Score $score)
    {
        if ($score->judge_id !== $request->user()->id) {
            return $this->error('Forbidden.', 403);
        }

        if ($score->status !== Score::STATUS_DRAFT) {
            return $this->error('Only draft scores can be updated.', 403);
        }

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0'],
        ]);

        $criterion = $score->criterion;
        if ((float) $data['score'] > (float) $criterion->max_score) {
            return $this->error('Score cannot exceed criterion max score.', 422);
        }

        $score->update(['score' => $data['score']]);

        return $this->respond([
            'id' => $score->id,
            'score' => $score->score,
            'status' => $score->status,
        ], 'Score updated.');
    }

    /**
     * Submit all this judge's draft scores for the event.
     */
    public function submitAll(Request $request, Event $event)
    {
        $judgeId = $request->user()->id;

        Score::where('event_id', $event->id)
            ->where('judge_id', $judgeId)
            ->where('status', Score::STATUS_DRAFT)
            ->update([
                'status' => Score::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

        $submittedCount = Score::where('event_id', $event->id)
            ->where('judge_id', $judgeId)
            ->whereIn('status', [Score::STATUS_SUBMITTED, Score::STATUS_APPROVED])
            ->count();

        return $this->respond(['submitted_count' => $submittedCount], 'All scores submitted.');
    }

    /**
     * Return only this judge's scores for the event.
     */
    public function myScores(Request $request, Event $event)
    {
        $scores = Score::where('event_id', $event->id)
            ->where('judge_id', $request->user()->id)
            ->with(['contestant:id,contestant_number,name', 'criterion:id,name,max_score,category_id'])
            ->get()
            ->map(fn (Score $s) => [
                'id' => $s->id,
                'contestant_id' => $s->contestant_id,
                'contestant_number' => $s->contestant?->contestant_number,
                'contestant_name' => $s->contestant?->name,
                'criterion_id' => $s->criterion_id,
                'criterion_name' => $s->criterion?->name,
                'max_score' => $s->criterion?->max_score,
                'score' => $s->score,
                'status' => $s->status,
            ]);

        return $this->respond($scores->values()->all(), 'OK.');
    }

    private function guard()
    {
        return request()->user();
    }
}
