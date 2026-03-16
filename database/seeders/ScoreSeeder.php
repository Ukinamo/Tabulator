<?php

namespace Database\Seeders;

use App\Models\Contestant;
use App\Models\Criterion;
use App\Models\Event;
use App\Models\Score;
use App\Models\User;
use App\Services\ScoreService;
use Illuminate\Database\Seeder;

class ScoreSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Event $event */
        $event = Event::where('name', 'Buwan ng Wika 2025 Talent Search')->firstOrFail();

        $judge = User::where('email', 'judge1@tabulation.com')->firstOrFail();

        $contestants = Contestant::where('event_id', $event->id)->get();
        $criteria = Criterion::whereIn(
            'category_id',
            $event->categories()->pluck('id'),
        )->get();

        foreach ($contestants as $contestant) {
            foreach ($criteria as $criterion) {
                $base = $criterion->max_score * 0.8;
                $scoreValue = round($base + random_int(0, 20) / 100, 2);

                Score::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'judge_id' => $judge->id,
                        'contestant_id' => $contestant->id,
                        'criterion_id' => $criterion->id,
                    ],
                    [
                        'score' => $scoreValue,
                        'status' => Score::STATUS_APPROVED,
                        'submitted_at' => now(),
                        'approved_at' => now(),
                    ],
                );
            }
        }

        /** @var ScoreService $service */
        $service = app(ScoreService::class);
        $service->recalculate($event);
    }
}

