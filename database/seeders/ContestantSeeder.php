<?php

namespace Database\Seeders;

use App\Models\Contestant;
use App\Models\Event;
use Illuminate\Database\Seeder;

class ContestantSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Event $event */
        $event = Event::where('name', 'Buwan ng Wika 2025 Talent Search')->firstOrFail();

        $contestants = [
            ['number' => '01', 'name' => 'Maria Clara Reyes'],
            ['number' => '02', 'name' => 'Ana Liza Bautista'],
            ['number' => '03', 'name' => 'Rosa Mae Delos Santos'],
            ['number' => '04', 'name' => 'Jennifer Cruz Mendoza'],
            ['number' => '05', 'name' => 'Cynthia Grace Villanueva'],
        ];

        foreach ($contestants as $entry) {
            Contestant::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'contestant_number' => $entry['number'],
                ],
                [
                    'name' => $entry['name'],
                    'bio' => null,
                    'photo_url' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}

