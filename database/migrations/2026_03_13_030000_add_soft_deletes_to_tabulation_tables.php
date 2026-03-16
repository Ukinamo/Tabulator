<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users soft deletes
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Events soft deletes
        Schema::table('events', function (Blueprint $table): void {
            if (! Schema::hasColumn('events', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Contestants soft deletes
        Schema::table('contestants', function (Blueprint $table): void {
            if (! Schema::hasColumn('contestants', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Categories soft deletes
        Schema::table('categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Criteria soft deletes
        Schema::table('criteria', function (Blueprint $table): void {
            if (! Schema::hasColumn('criteria', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Scores soft deletes
        Schema::table('scores', function (Blueprint $table): void {
            if (! Schema::hasColumn('scores', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('events', function (Blueprint $table): void {
            if (Schema::hasColumn('events', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('contestants', function (Blueprint $table): void {
            if (Schema::hasColumn('contestants', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('categories', function (Blueprint $table): void {
            if (Schema::hasColumn('categories', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('criteria', function (Blueprint $table): void {
            if (Schema::hasColumn('criteria', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('scores', function (Blueprint $table): void {
            if (Schema::hasColumn('scores', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

