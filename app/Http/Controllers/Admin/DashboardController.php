<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Contestant;
use App\Models\Event;
use App\Models\Score;
use App\Models\User;
use App\Services\ScoresheetService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ScoresheetService $scoresheet): Response
    {
        $event = Event::latest('event_date')->first();

        $stats = null;
        $judgeProgress = [];

        if ($event) {
            $totalContestants = Contestant::where('event_id', $event->id)->where('is_active', true)->count();
            $totalJudges = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count();
            $totalCategories = Category::where('event_id', $event->id)->count();

            $totalRequiredScores = 0;
            $submittedScores = 0;

            $progress = $scoresheet->getProgressForEvent($event->id);
            foreach ($progress as $row) {
                $totalRequiredScores = $row['total_count'];
                $submittedScores += $row['submitted_count'];
            }

            $stats = [
                'total_contestants' => $totalContestants,
                'total_judges' => $totalJudges,
                'total_categories' => $totalCategories,
                'submitted_scores' => $submittedScores,
                'required_scores' => $totalRequiredScores * max(1, $totalJudges),
            ];

            $judgeProgress = $progress;
        }

        return Inertia::render('admin/Dashboard', [
            'event' => $event,
            'stats' => $stats,
            'judgeProgress' => $judgeProgress,
            'canPublish' => $event !== null && $event->status === 'scoring',
        ]);
    }
}

