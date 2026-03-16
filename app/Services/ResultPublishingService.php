<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Result;
use App\Models\Score;
use Illuminate\Support\Facades\DB;

class ResultPublishingService
{
    public function __construct(
        protected ScoreService $scoreService,
    ) {
    }

    /**
     * Publish final results for an event.
     *
     * Steps:
     * 1. Verify all judges have submitted scores.
     * 2. Recalculate results.
     * 3. Mark results as published and assign reveal_order.
     * 4. Update event status.
     */
    public function publish(Event $event, int $superAdminId): void
    {
        DB::transaction(function () use ($event): void {
            // 1. Ensure there are no remaining draft scores for this event.
            $hasDrafts = Score::query()
                ->where('event_id', $event->id)
                ->where('status', Score::STATUS_DRAFT)
                ->exists();

            if ($hasDrafts) {
                throw new \RuntimeException('Not all scores have been submitted.');
            }

            // 2. Recalculate results.
            $this->scoreService->recalculate($event);

            // 3. Mark as published and assign reveal order (highest rank revealed first).
            $results = Result::where('event_id', $event->id)
                ->orderBy('rank')
                ->get();

            $total = $results->count();

            foreach ($results as $result) {
                $revealOrder = $total - $result->rank + 1;

                $result->update([
                    'is_published' => true,
                    'published_at' => now(),
                    'reveal_order' => $revealOrder,
                    'is_revealed' => false,
                    'revealed_at' => null,
                ]);
            }

            // 4. Update event status.
            $event->update([
                'status' => 'published',
            ]);
        });
    }

    /**
     * Reveal the next winner for the MC.
     *
     * Returns the revealed result or null when no more remain.
     */
    public function revealNext(Event $event, int $mcId): ?Result
    {
        /** @var Result|null $next */
        $next = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->orderBy('reveal_order', 'asc')
            ->lockForUpdate()
            ->first();

        if (! $next) {
            return null;
        }

        $next->update([
            'is_revealed' => true,
            'revealed_at' => now(),
        ]);

        return $next->load('contestant');
    }
}

