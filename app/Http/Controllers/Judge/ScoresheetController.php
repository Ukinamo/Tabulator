<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\ScoresheetService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScoresheetController extends Controller
{
    public function __invoke(Request $request, ScoresheetService $scoresheetService): Response
    {
        $event = Event::whereHas('contestants')
            ->where('status', 'scoring')
            ->latest('updated_at')
            ->first();

        $matrix = [];

        if ($event) {
            $matrix = $scoresheetService->getScoresheetForJudge(
                $request->user()->id,
                $event->id,
            );
        }

        return Inertia::render('judge/Scoresheet', [
            'event' => $event,
            'initialScoresheet' => $matrix,
        ]);
    }
}
