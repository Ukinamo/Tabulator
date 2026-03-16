<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Result;

class ResultController extends Controller
{
    /**
     * Full ranked results (published and unpublished) for admin/organizer view.
     */
    public function index(Event $event)
    {
        $results = Result::where('event_id', $event->id)
            ->orderBy('rank')
            ->with('contestant')
            ->get()
            ->map(fn (Result $r) => [
                'id' => $r->id,
                'rank' => $r->rank,
                'contestant_id' => $r->contestant_id,
                'contestant_number' => $r->contestant?->contestant_number,
                'contestant_name' => $r->contestant?->name,
                'final_score' => $r->final_score,
                'is_published' => $r->is_published,
                'is_revealed' => $r->is_revealed,
                'reveal_order' => $r->reveal_order,
            ]);

        return $this->respond($results->values()->all(), 'OK.');
    }
}
