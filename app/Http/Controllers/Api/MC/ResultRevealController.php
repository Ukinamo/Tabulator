<?php

namespace App\Http\Controllers\Api\MC;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Result;
use App\Services\ResultPublishingService;
use Illuminate\Http\Request;

class ResultRevealController extends Controller
{
    public function __construct(
        protected ResultPublishingService $publishing,
    ) {
    }

    /**
     * Return all already-revealed results for the latest event and whether more remain.
     */
    public function index(Request $request)
    {
        $event = Event::orderBy('event_date', 'desc')->firstOrFail();

        $revealed = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', true)
            ->orderBy('reveal_order', 'asc')
            ->with('contestant')
            ->get()
            ->map(function (Result $result) {
                return [
                    'id' => $result->id,
                    'rank' => $result->rank,
                    'contestant_name' => $result->contestant?->name,
                    'contestant_number' => $result->contestant?->contestant_number,
                    'final_score' => $result->final_score,
                ];
            })
            ->values()
            ->all();

        $hasMore = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->exists();

        $isPublished = Result::where('event_id', $event->id)->where('is_published', true)->exists();

        return $this->respond(
            [
                'revealed' => $revealed,
                'has_more' => $hasMore,
                'is_published' => $isPublished,
                'event_name' => $event->name,
            ],
            'Revealed results loaded.',
        );
    }

    /**
     * Reveal the next winner for the latest event.
     */
    public function reveal(Request $request)
    {
        $event = Event::orderBy('event_date', 'desc')->firstOrFail();

        $result = $this->publishing->revealNext($event, $request->user()->id);

        if (! $result) {
            return $this->respond(
                [
                    'result' => null,
                    'has_more' => false,
                ],
                'No more results to reveal.',
            );
        }

        $hasMore = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->exists();

        return $this->respond(
            [
                'result' => [
                    'id' => $result->id,
                    'rank' => $result->rank,
                    'contestant_name' => $result->contestant?->name,
                    'contestant_number' => $result->contestant?->contestant_number,
                    'final_score' => $result->final_score,
                ],
                'has_more' => $hasMore,
            ],
            'Next result revealed.',
        );
    }

    /**
     * Return already-revealed results for a specific event (for Phase 4 checklist).
     * GET /api/v1/mc/events/{event}/results
     */
    public function indexForEvent(Event $event)
    {
        $revealed = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', true)
            ->orderBy('reveal_order', 'asc')
            ->with('contestant')
            ->get()
            ->map(function (Result $result) {
                return [
                    'id' => $result->id,
                    'rank' => $result->rank,
                    'contestant_name' => $result->contestant?->name,
                    'contestant_number' => $result->contestant?->contestant_number,
                    'final_score' => $result->final_score,
                ];
            })
            ->values()
            ->all();

        $hasMore = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->exists();

        $isPublished = Result::where('event_id', $event->id)->where('is_published', true)->exists();

        return $this->respond(
            [
                'revealed' => $revealed,
                'has_more' => $hasMore,
                'is_published' => $isPublished,
            ],
            'Revealed results loaded.',
        );
    }

    /**
     * Reveal the next result for a specific event (for Phase 4 checklist).
     * POST /api/v1/mc/events/{event}/results/reveal
     */
    public function revealForEvent(Request $request, Event $event)
    {
        $result = $this->publishing->revealNext($event, $request->user()->id);

        if (! $result) {
            return $this->respond(
                [
                    'result' => null,
                    'has_more' => false,
                ],
                'No more results to reveal.',
            );
        }

        $hasMore = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->exists();

        return $this->respond(
            [
                'result' => [
                    'id' => $result->id,
                    'rank' => $result->rank,
                    'contestant_name' => $result->contestant?->name,
                    'contestant_number' => $result->contestant?->contestant_number,
                    'final_score' => $result->final_score,
                ],
                'has_more' => $hasMore,
            ],
            'Next result revealed.',
        );
    }
}
