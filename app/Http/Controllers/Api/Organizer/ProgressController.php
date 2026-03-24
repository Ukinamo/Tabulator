<?php

namespace App\Http\Controllers\Api\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\ScoresheetService;
use Illuminate\Http\Request;

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

    /**
     * Organizer submits scoring system to admins for review.
     * This moves the event into the "ongoing" state (pending admin approval).
     */
    public function openScoring(Request $request, Event $event)
    {
        if ($event->status === 'published') {
            return $this->error('Published events cannot be changed.', 422);
        }

        if ($event->status !== 'ongoing') {
            $event->update(['status' => 'ongoing']);
        }

        return $this->respond([
            'id' => $event->id,
            'name' => $event->name,
            'status' => $event->status,
        ], 'Scoring system submitted to admins.');
    }

    /**
     * Organizer retrieves a previously submitted scoring system from admin review.
     * This moves the event back to "setup".
     */
    public function retrieveScoring(Request $request, Event $event)
    {
        if ($event->status === 'published') {
            return $this->error('Published events cannot be changed.', 422);
        }

        if ($event->status === 'scoring') {
            return $this->error('Scoring is already open for judges and can no longer be retrieved by organizer.', 422);
        }

        if ($event->status !== 'ongoing') {
            return $this->error('This scoring system is not currently submitted to admins.', 422);
        }

        $event->update(['status' => 'setup']);

        return $this->respond([
            'id' => $event->id,
            'name' => $event->name,
            'status' => $event->status,
        ], 'Scoring system retrieved from admin review.');
    }
}
