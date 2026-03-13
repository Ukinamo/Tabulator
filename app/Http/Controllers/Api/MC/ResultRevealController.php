<?php

namespace App\Http\Controllers\Api\MC;

use App\Http\Controllers\Controller;
use App\Models\Result;
use Illuminate\Http\Request;

class ResultRevealController extends Controller
{
    public function index(Request $request)
    {
        $eventId = (int) $request->query('event_id');

        $query = Result::query()
            ->where('is_published', true)
            ->where('is_revealed', true)
            ->with('contestant');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $results = $query->orderBy('reveal_order')->get()->map(function (Result $result) {
            return [
                'reveal_order' => $result->reveal_order,
                'rank' => $result->rank,
                'contestant_name' => $result->contestant?->name,
                'contestant_number' => $result->contestant?->contestant_number,
                'is_revealed' => $result->is_revealed,
            ];
        });

        $nextRevealAvailable = Result::query()
            ->where('is_published', true)
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->where('is_revealed', false)
            ->exists();

        return response()->json([
            'data' => $results,
            'next_reveal_available' => $nextRevealAvailable,
        ]);
    }

    public function reveal(Result $result)
    {
        if (! $result->is_published || $result->is_revealed) {
            return response()->json([
                'message' => 'Result cannot be revealed.',
            ], 422);
        }

        $maxOrder = Result::where('event_id', $result->event_id)->max('reveal_order') ?? 0;

        $result->update([
            'is_revealed' => true,
            'reveal_order' => $maxOrder + 1,
            'revealed_at' => now(),
        ]);

        return response()->json(['data' => $result]);
    }
}

