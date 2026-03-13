<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Event::orderByDesc('event_date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['setup', 'ongoing', 'scoring', 'published'])],
        ]);

        $event = Event::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'venue' => $data['venue'] ?? null,
            'event_date' => $data['event_date'],
            'status' => $data['status'] ?? 'setup',
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $event], 201);
    }

    public function show(Event $event)
    {
        return response()->json(['data' => $event]);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'venue' => ['sometimes', 'nullable', 'string', 'max:255'],
            'event_date' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::in(['setup', 'ongoing', 'scoring', 'published'])],
        ]);

        $event->update($data);

        return response()->json(['data' => $event]);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([], 204);
    }
}

