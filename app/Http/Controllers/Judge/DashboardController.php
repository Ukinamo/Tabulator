<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\Contestant;
use App\Models\Event;
use App\Models\Score;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $event = Event::latest('event_date')->first();

        $summary = null;
        $status = null;

        if ($event) {
            $scores = Score::query()
                ->where('event_id', $event->id)
                ->where('judge_id', $user->id)
                ->get();

            $submittedCount = $scores->whereIn('status', [Score::STATUS_SUBMITTED, Score::STATUS_APPROVED])->count();

            // Total required = all active contestants × all criteria (so new contestants are included)
            $contestantCount = Contestant::where('event_id', $event->id)->where('is_active', true)->count();
            $criteriaCount = $event->categories()->withCount('criteria')->get()->sum('criteria_count');
            $totalRequired = $contestantCount * $criteriaCount;

            $remaining = max(0, $totalRequired - $submittedCount);

            if ($remaining > 0) {
                $status = [
                    'type' => 'pending',
                    'message' => "{$remaining} score(s) remaining to submit.",
                ];
            } elseif ($totalRequired > 0) {
                $status = [
                    'type' => 'submitted',
                    'message' => 'All of your scores have been submitted.',
                ];
            }

            $byCategory = $scores
                ->load('criterion.category')
                ->groupBy(fn ($score) => $score->criterion->category->name ?? 'Unknown')
                ->map(function ($group) {
                    $avg = $group->avg('score') ?: 0;

                    return [
                        'average' => round($avg, 2),
                        'count' => $group->count(),
                    ];
                });

            $summary = $byCategory;
        }

        return Inertia::render('judge/Dashboard', [
            'user' => [
                'name' => $user->name,
            ],
            'event' => $event,
            'statusSummary' => $status,
            'categorySummary' => $summary,
        ]);
    }
}

