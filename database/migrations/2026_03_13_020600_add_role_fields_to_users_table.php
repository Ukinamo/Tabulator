<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['super_admin', 'admin', 'mc', 'organizer'])
                ->default('admin')
                ->after('password');

            $table->boolean('is_active')
                ->default(true)
                ->after('role');

            $table->foreignId('created_by')
                ->nullable()
                ->after('is_active')
                ->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['role', 'is_active']);
        });
    }
};

