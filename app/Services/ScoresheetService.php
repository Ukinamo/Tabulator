<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Contestant;
use App\Models\Event;
use App\Models\Score;
use App\Models\User;

class ScoresheetService
{
    /**
     * Build the scoresheet matrix for a given judge and event.
     *
     * Structure:
     * [
     *   contestant_id => [
     *     'contestant' => [...],
     *     'categories' => [
     *       category_id => [
     *         'category' => [...],
     *         'criteria' => [
     *           criterion_id => { id, name, max_score, current_score, status }
     *         ],
     *       ],
     *     ],
     *   ],
     * ]
     */
    public function getScoresheetForJudge(int $judgeId, int $eventId): array
    {
        /** @var Event $event */
        $event = Event::with([
            // Use all (non–soft-deleted) contestants for the event, regardless of is_active flag,
            // so that judges always see the configured lineup.
            'contestants' => fn ($q) => $q->orderBy('contestant_number'),
            'categories.criteria' => fn ($q) => $q->orderBy('sort_order'),
        ])->findOrFail($eventId);

        $scores = Score::query()
            ->where('event_id', $event->id)
            ->where('judge_id', $judgeId)
            ->get()
            ->keyBy(fn (Score $score) => $score->contestant_id.'-'.$score->criterion_id);

        $matrix = [];

        foreach ($event->contestants as $contestant) {
            $contestantEntry = [
                'contestant' => [
                    'id' => $contestant->id,
                    'number' => $contestant->contestant_number,
                    'name' => $contestant->name,
                ],
                'categories' => [],
            ];

            foreach ($event->categories as $category) {
                $categoryEntry = [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'weight' => $category->weight,
                    ],
                    'criteria' => [],
                ];

                foreach ($category->criteria as $criterion) {
                    $key = $contestant->id.'-'.$criterion->id;
                    /** @var \App\Models\Score|null $score */
                    $score = $scores->get($key);

                    $categoryEntry['criteria'][] = [
                        'id' => $criterion->id,
                        'name' => $criterion->name,
                        'max_score' => $criterion->max_score,
                        'current_score' => $score?->score,
                        'status' => $score?->status ?? Score::STATUS_DRAFT,
                    ];
                }

                $contestantEntry['categories'][] = $categoryEntry;
            }

            $matrix[] = $contestantEntry;
        }

        return $matrix;
    }

    /**
     * Get per-judge scoring progress for an event.
     *
     * Returns:
     * [
     *   { judge_id, judge_name, submitted_count, total_count, status },
     * ]
     */
    public function getProgressForEvent(int $eventId): array
    {
        /** @var Event $event */
        $event = Event::findOrFail($eventId);

        $judges = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->get();

        $criteriaCount = Category::where('event_id', $eventId)
            ->withCount('criteria')
            ->get()
            ->sum('criteria_count');

        $contestantCount = Contestant::where('event_id', $eventId)
            ->where('is_active', true)
            ->count();

        $totalRequiredPerJudge = $criteriaCount * $contestantCount;

        $progress = [];

        foreach ($judges as $judge) {
            $submittedCount = Score::query()
                ->where('event_id', $eventId)
                ->where('judge_id', $judge->id)
                ->whereIn('status', [Score::STATUS_SUBMITTED, Score::STATUS_APPROVED])
                ->count();

            $status = 'not_started';

            if ($submittedCount > 0 && $submittedCount < $totalRequiredPerJudge) {
                $status = 'in_progress';
            } elseif ($submittedCount >= $totalRequiredPerJudge && $totalRequiredPerJudge > 0) {
                $status = 'submitted';
            }

            $progress[] = [
                'judge_id' => $judge->id,
                'judge_name' => $judge->name,
                'submitted_count' => $submittedCount,
                'total_count' => $totalRequiredPerJudge,
                'status' => $status,
            ];
        }

        return $progress;
    }
}

