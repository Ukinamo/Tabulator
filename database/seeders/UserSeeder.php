<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@tabulation.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ],
        );

        User::firstOrCreate(
            ['email' => 'judge1@tabulation.com'],
            [
                'name' => 'Judge Maria Santos',
                'password' => Hash::make('judge123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
                'created_by' => $superAdmin->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'mc@tabulation.com'],
            [
                'name' => 'Event MC',
                'password' => Hash::make('mc123'),
                'role' => User::ROLE_MC,
                'is_active' => true,
                'created_by' => $superAdmin->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'organizer@tabulation.com'],
            [
                'name' => 'Event Organizer',
                'password' => Hash::make('organizer123'),
                'role' => User::ROLE_ORGANIZER,
                'is_active' => true,
                'created_by' => $superAdmin->id,
            ],
        );
    }
}

