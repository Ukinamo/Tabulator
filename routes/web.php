<?php

use App\Http\Middleware\CheckRole;
use App\Http\Controllers\Admin\ContestantsController as AdminContestantsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventSetupController as AdminEventSetupController;
use App\Http\Controllers\Admin\GatewayController as AdminGatewayController;
use App\Http\Controllers\Admin\ResultsController as AdminResultsController;
use App\Http\Controllers\Admin\ScoreReviewController as AdminScoreReviewController;
use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Judge\DashboardController as JudgeDashboardController;
use App\Http\Controllers\Judge\ScoresheetController as JudgeScoresheetController;
use App\Http\Controllers\Organizer\CategoriesController as OrganizerCategoriesController;
use App\Http\Controllers\Organizer\CriteriaController as OrganizerCriteriaController;
use App\Http\Controllers\Organizer\DashboardController as OrganizerDashboardController;
use App\Http\Controllers\Organizer\ProgressController as OrganizerProgressController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // MC live reveal screen
    Route::inertia('mc/reveal', 'mc/Reveal')
        ->middleware('role:mc')
        ->name('mc.reveal');

    // Super Admin
    Route::middleware(['role:super_admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
            Route::get('/users', AdminUsersController::class)->name('users');
            Route::get('/event', AdminEventSetupController::class)->name('event');
            Route::get('/gateway', AdminGatewayController::class)->name('gateway');
            Route::get('/contestants', AdminContestantsController::class)->name('contestants');
            Route::get('/scores', AdminScoreReviewController::class)->name('scores');
            Route::get('/results', AdminResultsController::class)->name('results');
        });

    // Organizer (super_admin or organizer)
    Route::middleware(['role:super_admin,organizer'])
        ->prefix('organizer')
        ->name('organizer.')
        ->group(function (): void {
            Route::get('/dashboard', OrganizerDashboardController::class)->name('dashboard');
            Route::get('/categories', OrganizerCategoriesController::class)->name('categories');
            Route::get('/criteria', OrganizerCriteriaController::class)->name('criteria');
            Route::get('/progress', OrganizerProgressController::class)->name('progress');
        });
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Judge (role:admin)
    Route::middleware(['role:admin'])
        ->prefix('judge')
        ->name('judge.')
        ->group(function (): void {
            Route::get('/dashboard', JudgeDashboardController::class)->name('dashboard');
            Route::get('/scoresheet', JudgeScoresheetController::class)->name('scoresheet');
        });
});

require __DIR__.'/settings.php';
