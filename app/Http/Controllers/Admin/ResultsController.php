<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $event = Event::latest('event_date')->first();

        return Inertia::render('admin/Results', [
            'event' => $event,
        ]);
    }
}
