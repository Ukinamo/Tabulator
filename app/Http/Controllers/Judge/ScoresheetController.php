<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScoresheetController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // Use the most recent event that has contestants, regardless of status,
        // so judges always receive the configured scoring setup that admins delivered.
        $event = Event::whereHas('contestants')
            ->latest('event_date')
            ->first();

        return Inertia::render('judge/Scoresheet', [
            'event' => $event,
        ]);
    }
}
