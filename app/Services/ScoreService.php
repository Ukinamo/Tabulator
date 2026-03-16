<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Result;

class ScoreService
{
    public function __construct(
        protected ScoreCalculationService $calculator,
    ) {
    }

    /**
     * Recalculate and persist results for all contestants in the given event.
     */
    public function recalculate(Event $event): void
    {
        $computed = $this->calculator->computeResults($event);

        $rank = 1;

        foreach ($computed as $row) {
            /** @var \App\Models\Contestant $contestant */
            $contestant = $row['contestant'];
            $finalScore = $row['final_score'];

            Result::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'contestant_id' => $contestant->id,
                ],
                [
                    'final_score' => $finalScore,
                    'rank' => $rank,
                ],
            );

            $rank++;
        }
    }
}

