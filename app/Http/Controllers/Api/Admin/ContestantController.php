<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contestant;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContestantController extends Controller
{
    /**
     * List contestants for the event.
     */
    public function index(Event $event)
    {
        $contestants = $event->contestants()->orderBy('contestant_number')->get()->map(fn (Contestant $c) => [
            'id' => $c->id,
            'contestant_number' => $c->contestant_number,
            'name' => $c->name,
            'bio' => $c->bio,
            'photo_url' => $c->photo_url,
            'is_active' => $c->is_active,
        ]);

        return $this->respond($contestants->values()->all(), 'OK.');
    }

    /**
     * Create contestant for the event.
     */
    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'contestant_number' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $exists = $event->contestants()->where('contestant_number', $data['contestant_number'])->exists();
        if ($exists) {
            return $this->error('Contestant number already exists for this event.', 422);
        }

        $data['event_id'] = $event->id;
        $data['is_active'] = $data['is_active'] ?? true;
        $contestant = Contestant::create($data);

        return $this->respond([
            'id' => $contestant->id,
            'contestant_number' => $contestant->contestant_number,
            'name' => $contestant->name,
            'bio' => $contestant->bio,
            'photo_url' => $contestant->photo_url,
            'is_active' => $contestant->is_active,
        ], 'Contestant created.', 201);
    }

    /**
     * Show single contestant.
     */
    public function show(Event $event, Contestant $contestant)
    {
        if ($contestant->event_id !== $event->id) {
            return $this->error('Contestant not found for this event.', 404);
        }

        return $this->respond([
            'id' => $contestant->id,
            'contestant_number' => $contestant->contestant_number,
            'name' => $contestant->name,
            'bio' => $contestant->bio,
            'photo_url' => $contestant->photo_url,
            'is_active' => $contestant->is_active,
        ], 'OK.');
    }

    /**
     * Update contestant.
     */
    public function update(Request $request, Event $event, Contestant $contestant)
    {
        if ($contestant->event_id !== $event->id) {
            return $this->error('Contestant not found for this event.', 404);
        }

        $data = $request->validate([
            'contestant_number' => ['sometimes', 'string', 'max:20'],
            'name' => ['sometimes', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['contestant_number'])) {
            $exists = $event->contestants()->where('contestant_number', $data['contestant_number'])->where('id', '!=', $contestant->id)->exists();
            if ($exists) {
                return $this->error('Contestant number already exists for this event.', 422);
            }
        }

        $contestant->update($data);

        return $this->respond([
            'id' => $contestant->id,
            'contestant_number' => $contestant->contestant_number,
            'name' => $contestant->name,
            'bio' => $contestant->bio,
            'photo_url' => $contestant->photo_url,
            'is_active' => $contestant->is_active,
        ], 'Contestant updated.');
    }

    /**
     * Soft delete contestant. Scores remain in DB but contestant is excluded from results and lists.
     */
    public function destroy(Event $event, Contestant $contestant)
    {
        if ($contestant->event_id !== $event->id) {
            return $this->error('Contestant not found for this event.', 404);
        }

        $contestant->delete();

        return $this->respond(null, 'Contestant deleted.', 204);
    }

    /**
     * Upload a photo for a contestant. Returns the public URL to store in photo_url.
     */
    public function uploadPhoto(Request $request, Event $event)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $file = $request->file('photo');
        $path = $file->store('contestants', 'public');
        $url = Storage::disk('public')->url($path);

        return $this->respond(['url' => $url], 'Photo uploaded.', 201);
    }
}
