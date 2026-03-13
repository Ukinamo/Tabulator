<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contestant;
use App\Models\Event;
use Illuminate\Http\Request;

class ContestantController extends Controller
{
    public function index(Event $event)
    {
        return response()->json([
            'data' => $event->contestants()->orderBy('contestant_number')->get(),
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'contestant_number' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string', 'max:500'],
        ]);

        $exists = $event->contestants()
            ->where('contestant_number', $data['contestant_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Contestant number must be unique per event.',
            ], 422);
        }

        $contestant = $event->contestants()->create($data);

        return response()->json(['data' => $contestant], 201);
    }

    public function show(Event $event, Contestant $contestant)
    {
        abort_unless($contestant->event_id === $event->id, 404);

        return response()->json(['data' => $contestant]);
    }

    public function update(Request $request, Event $event, Contestant $contestant)
    {
        abort_unless($contestant->event_id === $event->id, 404);

        $data = $request->validate([
            'contestant_number' => ['sometimes', 'string', 'max:20'],
            'name' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'photo_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['contestant_number']) && $data['contestant_number'] !== $contestant->contestant_number) {
            $exists = $event->contestants()
                ->where('contestant_number', $data['contestant_number'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Contestant number must be unique per event.',
                ], 422);
            }
        }

        $contestant->update($data);

        return response()->json(['data' => $contestant]);
    }

    public function destroy(Event $event, Contestant $contestant)
    {
        abort_unless($contestant->event_id === $event->id, 404);

        $contestant->delete();

        return response()->json([], 204);
    }
}

