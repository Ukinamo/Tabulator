<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve by role so the super admin email can change in UserSeeder without breaking this seeder.
        $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

        Event::firstOrCreate(
            ['name' => 'Buwan ng Wika 2025 Talent Search'],
            [
                'description' => 'Demo event for the tabulation system.',
                'venue' => 'Pampanga Civic Center, San Fernando, Pampanga',
                'event_date' => now()->toDateString(),
                'status' => 'scoring',
                'created_by' => $superAdmin->id,
            ],
        );
    }
}

