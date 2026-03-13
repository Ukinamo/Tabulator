<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Event;
use App\Models\Score;
use Illuminate\Support\Collection;

class ScoreCalculationService
{
    /**
     * Compute weighted final scores for all active contestants in an event.
     *
     * Mirrors the design documentation formula:
     * Final Score = Σ (Category Weight × Average Judge Score for that Category)
     */
    public function computeResults(Event $event): Collection
    {
        $contestants = $event->contestants()->where('is_active', true)->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, Category> $categories */
        $categories = $event->categories()->with('criteria')->get();

        return $contestants
            ->map(function ($contestant) use ($categories) {
                $finalScore = $categories->sum(function (Category $category) use ($contestant) {
                    $criteriaIds = $category->criteria->pluck('id');

                    $avgScore = Score::query()
                        ->where('contestant_id', $contestant->id)
                        ->whereIn('criterion_id', $criteriaIds)
                        ->where('status', Score::STATUS_APPROVED)
                        ->avg('score') ?? 0.0;

                    return ($category->weight / 100) * $avgScore;
                });

                return [
                    'contestant' => $contestant,
                    'final_score' => round($finalScore, 4),
                ];
            })
            ->sortByDesc('final_score')
            ->values();
    }
}

