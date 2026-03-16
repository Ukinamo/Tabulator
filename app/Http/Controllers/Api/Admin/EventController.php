<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Score;
use App\Services\ResultPublishingService;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function __construct(
        protected ResultPublishingService $publishing,
        protected ScoreService $scoreService,
    ) {
    }

    /**
     * List all events with status.
     */
    public function index(Request $request)
    {
        $events = Event::with('creator:id,name')
            ->orderBy('event_date', 'desc')
            ->get()
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'description' => $e->description,
                'venue' => $e->venue,
                'event_date' => $e->event_date,
                'status' => $e->status,
            ]);

        return $this->respond($events, 'OK.');
    }

    /**
     * Create a new event.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(['setup', 'ongoing', 'scoring', 'published'])],
        ]);

        $data['created_by'] = $request->user()->id;
        $event = Event::create($data);

        return $this->respond([
            'id' => $event->id,
            'name' => $event->name,
            'venue' => $event->venue,
            'event_date' => $event->event_date,
            'status' => $event->status,
        ], 'Event created.', 201);
    }

    /**
     * Show single event.
     */
    public function show(Event $event)
    {
        return $this->respond([
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'venue' => $event->venue,
            'event_date' => $event->event_date,
            'status' => $event->status,
        ], 'OK.');
    }

    /**
     * Update event name, venue, date.
     */
    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_date' => ['sometimes', 'date'],
<<<<<<< HEAD
=======
            'status' => ['sometimes', Rule::in(['setup', 'ongoing', 'scoring', 'published'])],
>>>>>>> 81bfbe3f352f53bb82dd50bd6bce7078f6524cb9
        ]);

        $event->update($data);

        return $this->respond([
            'id' => $event->id,
            'name' => $event->name,
            'venue' => $event->venue,
            'event_date' => $event->event_date,
            'status' => $event->status,
        ], 'Event updated.');
    }

    /**
     * Soft delete not used for events per RULE 5; use status or leave destroy no-op / 403.
     */
    public function destroy(Event $event)
    {
        return $this->error('Events cannot be deleted.', 403);
    }

    /**
     * Publish results: recalculate, set is_published, assign reveal_order, set event status.
     */
    public function publish(Request $request, Event $event)
    {
        try {
            $this->publishing->publish($event, $request->user()->id);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $results = $event->results()->orderBy('rank')->with('contestant')->get()->map(fn ($r) => [
            'rank' => $r->rank,
            'contestant_id' => $r->contestant_id,
            'contestant_name' => $r->contestant?->name,
            'contestant_number' => $r->contestant?->contestant_number,
            'final_score' => $r->final_score,
        ]);

        return $this->respond($results->values()->all(), 'Results published.');
    }

    /**
     * Reset a judge's submitted scores back to draft.
     */
    public function unlockScoring(Request $request, Event $event, int $judge)
    {
        Score::where('event_id', $event->id)->where('judge_id', $judge)->whereIn('status', ['submitted', 'approved'])->update([
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
        ]);

        $this->scoreService->recalculate($event);

        return $this->respond(null, 'Judge scoring unlocked.');
    }

    /**
     * Admin delivers the prepared scoring system to judges by opening scoring.
     */
    public function startScoring(Request $request, Event $event)
    {
        if ($event->status === 'published') {
            return $this->error('Published events cannot be moved back to scoring.', 422);
        }

        if ($event->status !== 'scoring') {
            $event->update(['status' => 'scoring']);
        }

        return $this->respond([
            'id' => $event->id,
            'name' => $event->name,
            'status' => $event->status,
        ], 'Scoring opened for judges.');
    }
}
