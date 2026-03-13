<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('venue')->nullable();
            $table->date('event_date');
            $table->enum('status', ['setup', 'ongoing', 'scoring', 'published'])->default('setup');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('contestants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('contestant_number', 20);
            $table->string('name');
            $table->text('bio')->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_id', 'contestant_number']);
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 5, 2);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('criteria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('max_score', 6, 2);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('users');
            $table->foreignId('contestant_id')->constrained('contestants')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('score', 6, 2);
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['judge_id', 'contestant_id', 'criterion_id'], 'scores_unique_per_judge');
        });

        Schema::create('results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('contestant_id')->constrained('contestants')->cascadeOnDelete();
            $table->decimal('final_score', 8, 4);
            $table->integer('rank');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_revealed')->default(false);
            $table->integer('reveal_order')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('revealed_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'contestant_id'], 'results_unique_per_event_contestant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
        Schema::dropIfExists('scores');
        Schema::dropIfExists('criteria');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('contestants');
        Schema::dropIfExists('events');
    }
};

