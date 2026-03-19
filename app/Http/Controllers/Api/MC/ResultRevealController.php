<?php

namespace App\Http\Controllers\Api\MC;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Result;
use App\Services\ResultPublishingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $event = Event::latest('updated_at')->firstOrFail();

        $topN = ResultPublishingService::TOP_N;

        $revealed = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', true)
            ->where('rank', '<=', $topN)
            ->orderBy('rank', 'asc')
            ->with('contestant')
            ->get()
            ->map(fn (Result $r) => $this->formatResult($r))
            ->values()
            ->all();

        $hasMore = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->where('rank', '<=', $topN)
            ->exists();

        $isPublished = Result::where('event_id', $event->id)->where('is_published', true)->exists();

        $consolation = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('rank', '>', $topN)
            ->orderBy('rank', 'asc')
            ->with('contestant')
            ->get()
            ->map(fn (Result $r) => $this->formatResult($r))
            ->values()
            ->all();

        return $this->respond(
            [
                'revealed' => $revealed,
                'has_more' => $hasMore,
                'is_published' => $isPublished,
                'event_name' => $event->name,
                'consolation' => $consolation,
            ],
            'Revealed results loaded.',
        );
    }

    /**
     * Reveal the next winner for the latest event.
     */
    public function reveal(Request $request)
    {
        $event = Event::latest('updated_at')->firstOrFail();

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

        $topN = ResultPublishingService::TOP_N;

        $hasMore = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->where('rank', '<=', $topN)
            ->exists();

        return $this->respond(
            [
                'result' => $this->formatResult($result),
                'has_more' => $hasMore,
            ],
            'Next result revealed.',
        );
    }

    /**
     * Reset all revealed results so the MC can redo the reveal ceremony.
     */
    public function clearRevealed(Request $request)
    {
        $event = Event::latest('updated_at')->firstOrFail();

        Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', true)
            ->where('rank', '<=', ResultPublishingService::TOP_N)
            ->update([
                'is_revealed' => false,
                'revealed_at' => null,
            ]);

        return $this->respond(null, 'All reveals cleared. Ready to start over.');
    }

    /**
     * Return already-revealed results for a specific event (for Phase 4 checklist).
     */
    public function indexForEvent(Event $event)
    {
        $topN = ResultPublishingService::TOP_N;

        $revealed = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', true)
            ->where('rank', '<=', $topN)
            ->orderBy('rank', 'asc')
            ->with('contestant')
            ->get()
            ->map(fn (Result $r) => $this->formatResult($r))
            ->values()
            ->all();

        $hasMore = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->where('rank', '<=', $topN)
            ->exists();

        $isPublished = Result::where('event_id', $event->id)->where('is_published', true)->exists();

        $consolation = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('rank', '>', $topN)
            ->orderBy('rank', 'asc')
            ->with('contestant')
            ->get()
            ->map(fn (Result $r) => $this->formatResult($r))
            ->values()
            ->all();

        return $this->respond(
            [
                'revealed' => $revealed,
                'has_more' => $hasMore,
                'is_published' => $isPublished,
                'consolation' => $consolation,
            ],
            'Revealed results loaded.',
        );
    }

    /**
     * Reveal the next result for a specific event (for Phase 4 checklist).
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

        $topN = ResultPublishingService::TOP_N;

        $hasMore = Result::where('event_id', $event->id)
            ->where('is_published', true)
            ->where('is_revealed', false)
            ->where('rank', '<=', $topN)
            ->exists();

        return $this->respond(
            [
                'result' => $this->formatResult($result),
                'has_more' => $hasMore,
            ],
            'Next result revealed.',
        );
    }

    private function formatResult(Result $result): array
    {
        return [
            'id' => $result->id,
            'rank' => $result->rank,
            'contestant_name' => $result->contestant?->name,
            'contestant_number' => $result->contestant?->contestant_number,
            'photo_url' => $result->contestant?->photo_url,
            'final_score' => $result->final_score,
        ];
    }
}
