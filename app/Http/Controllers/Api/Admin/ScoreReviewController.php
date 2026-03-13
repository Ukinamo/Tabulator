<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Score;

class ScoreReviewController extends Controller
{
    public function index()
    {
        $scores = Score::query()
            ->with(['judge', 'contestant', 'criterion'])
            ->whereIn('status', [Score::STATUS_SUBMITTED, Score::STATUS_APPROVED])
            ->orderBy('event_id')
            ->orderBy('judge_id')
            ->get();

        return response()->json(['data' => $scores]);
    }

    public function approve(Score $score)
    {
        $score->update([
            'status' => Score::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        return response()->json(['data' => $score]);
    }
}

