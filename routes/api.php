<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\ContestantController;
use App\Http\Controllers\Api\Admin\EventController;
use App\Http\Controllers\Api\Admin\ScoreReviewController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Judge\ScoreController;
use App\Http\Controllers\Api\Judge\ContestantViewController;
use App\Http\Controllers\Api\MC\ResultRevealController;
use App\Http\Controllers\Api\Organizer\CategoryController;
use App\Http\Controllers\Api\Organizer\CriterionController;
use App\Http\Controllers\Api\ResultController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Super Admin routes
    Route::middleware('role:super_admin')->prefix('admin')->group(function (): void {
        Route::apiResource('users', UserController::class);
        Route::apiResource('events', EventController::class);
        Route::apiResource('events.contestants', ContestantController::class);

        Route::get('scores/review', [ScoreReviewController::class, 'index']);
        Route::post('scores/{score}/approve', [ScoreReviewController::class, 'approve']);

        Route::post('events/{event}/publish', [ResultController::class, 'publish']);
        Route::post('results/{result}/unlock-reveal', [ResultController::class, 'unlockReveal']);
    });

    // Organizer routes
    Route::middleware('role:super_admin,organizer')->prefix('organizer')->group(function (): void {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('categories.criteria', CriterionController::class);
        Route::get('events/{event}/progress', [CategoryController::class, 'scoringProgress']);
    });

    // Judge routes
    Route::middleware('role:admin')->prefix('judge')->group(function (): void {
        Route::get('contestants', [ContestantViewController::class, 'index']);
        Route::get('scoresheet', [ScoreController::class, 'scoresheet']);
        Route::post('scores', [ScoreController::class, 'store']);
        Route::put('scores/{score}', [ScoreController::class, 'update']);
        Route::post('scores/submit', [ScoreController::class, 'submitAll']);
    });

    // MC routes
    Route::middleware('role:mc')->prefix('mc')->group(function (): void {
        Route::get('results', [ResultRevealController::class, 'index']);
        Route::post('results/{result}/reveal', [ResultRevealController::class, 'reveal']);
    });
});
