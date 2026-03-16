<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\ScoresheetService;

class ProgressController extends Controller
{
    /**
     * Per-judge submission status for an event.
     */
    public function index(Event $event)
    {
        $service = app(ScoresheetService::class);

        return $this->respond($service->getProgressForEvent($event->id), 'OK.');
    }
}
