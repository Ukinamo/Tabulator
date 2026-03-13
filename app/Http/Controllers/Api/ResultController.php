<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Result;
use App\Services\ScoreCalculationService;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function __construct(
        private readonly ScoreCalculationService $scoreCalculationService,
    ) {
    }

    public function publish(Request $request, Event $event)
    {
        $results = $this->scoreCalculationService->computeResults($event);

        Result::where('event_id', $event->id)->delete();

        $rank = 1;
        foreach ($results as $row) {
            Result::create([
                'event_id' => $event->id,
                'contestant_id' => $row['contestant']->id,
                'final_score' => $row['final_score'],
                'rank' => $rank++,
                'is_published' => true,
                'is_revealed' => false,
                'reveal_order' => null,
                'published_at' => now(),
            ]);
        }

        $payload = Result::where('event_id', $event->id)
            ->orderBy('rank')
            ->with('contestant')
            ->get()
            ->map(fn (Result $result) => [
                'rank' => $result->rank,
                'contestant' => $result->contestant?->name,
                'final_score' => $result->final_score,
            ])
            ->values();

        $event->update(['status' => 'published']);

        return response()->json([
            'message' => 'Results published successfully.',
            'results' => $payload,
        ]);
    }

    public function unlockReveal(Result $result)
    {
        $result->update([
            'is_published' => true,
        ]);

        return response()->json(['data' => $result]);
    }
}

