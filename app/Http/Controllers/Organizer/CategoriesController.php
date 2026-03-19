<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $event = Event::latest('updated_at')->first();

        return Inertia::render('organizer/Categories', [
            'event' => $event,
        ]);
    }
}
