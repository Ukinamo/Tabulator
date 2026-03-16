<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Services\ScoresheetService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ScoresheetService $scoresheet): Response
    {
        $event = Event::latest('event_date')->first();

        $weights = null;
        $judgeProgress = [];

        if ($event) {
            $categories = Category::where('event_id', $event->id)->get();
            $totalWeight = (float) $categories->sum('weight');

            $weights = [
                'total' => $totalWeight,
                'categories' => $categories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'weight' => (float) $c->weight,
                ]),
            ];

            $judgeProgress = $scoresheet->getProgressForEvent($event->id);
        }

        return Inertia::render('organizer/Dashboard', [
            'event' => $event,
            'weights' => $weights,
            'judgeProgress' => $judgeProgress,
        ]);
    }
}

