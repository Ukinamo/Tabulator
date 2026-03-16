<?php

namespace App\Http\Controllers\Api\Judge;

use App\Http\Controllers\Controller;
use App\Models\Contestant;
use Illuminate\Http\Request;

class ContestantViewController extends Controller
{
    public function index(Request $request)
    {
        $eventId = $request->query('event_id');

        $query = Contestant::query()->where('is_active', true);

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        return response()->json([
            'data' => $query->orderBy('contestant_number')->get(),
        ]);
    }
}

