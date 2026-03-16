<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        /** @var Event $event */
        $event = Event::where('name', 'Buwan ng Wika 2025 Talent Search')->firstOrFail();

        $categories = [
            ['name' => 'Talent Portion', 'weight' => 40.00, 'sort_order' => 1],
            ['name' => 'Q&A Portion', 'weight' => 35.00, 'sort_order' => 2],
            ['name' => 'Attire & Runway', 'weight' => 25.00, 'sort_order' => 3],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => $data['name'],
                ],
                [
                    'weight' => $data['weight'],
                    'description' => null,
                    'sort_order' => $data['sort_order'],
                ],
            );
        }
    }
}

