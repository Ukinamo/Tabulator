<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Result;
use Illuminate\Database\Seeder;

class ResultSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Event $event */
        $event = Event::where('name', 'Buwan ng Wika 2025 Talent Search')->firstOrFail();

        $results = Result::where('event_id', $event->id)
            ->orderBy('rank')
            ->get();

        $total = $results->count();

        foreach ($results as $result) {
            $revealOrder = $total - $result->rank + 1;

            $result->update([
                'is_published' => true,
                'is_revealed' => false,
                'reveal_order' => $revealOrder,
                'published_at' => now(),
                'revealed_at' => null,
            ]);
        }
    }
}

