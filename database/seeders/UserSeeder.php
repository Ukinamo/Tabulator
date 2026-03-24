<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Plain passwords: User model uses the `hashed` cast and hashes on save.
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => 'admin123',
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ],
        );

        User::firstOrCreate(
            ['email' => 'judge1@tabulation.com'],
            [
                'name' => 'Judge Maria Santos',
                'password' => 'judge123',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'created_by' => $superAdmin->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'mc@tabulation.com'],
            [
                'name' => 'Event MC',
                'password' => 'mc123',
                'role' => User::ROLE_MC,
                'is_active' => true,
                'created_by' => $superAdmin->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'organizer@tabulation.com'],
            [
                'name' => 'Event Organizer',
                'password' => 'organizer123',
                'role' => User::ROLE_ORGANIZER,
                'is_active' => true,
                'created_by' => $superAdmin->id,
            ],
        );
    }
}

