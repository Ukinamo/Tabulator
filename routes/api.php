<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\ContestantController;
use App\Http\Controllers\Api\Admin\EventController;
use App\Http\Controllers\Api\Admin\ScoreReviewController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Judge\ScoreController;
use App\Http\Controllers\Api\MC\ResultRevealController;
use App\Http\Controllers\Api\Organizer\CategoryController;
use App\Http\Controllers\Api\Organizer\CriterionController;
use App\Http\Controllers\Api\Organizer\ProgressController;
use App\Http\Controllers\Api\ResultController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Public auth
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        // Super Admin
        Route::middleware('role:super_admin')->prefix('admin')->group(function (): void {
            Route::apiResource('users', UserController::class);
            Route::apiResource('events', EventController::class);
            Route::post('events/{event}/publish', [EventController::class, 'publish']);
            Route::post('events/{event}/unlock/{judge}', [EventController::class, 'unlockScoring']);
            Route::post('events/{event}/start-scoring', [EventController::class, 'startScoring']);
            Route::post('events/{event}/contestants/upload-photo', [ContestantController::class, 'uploadPhoto']);
            Route::apiResource('events.contestants', ContestantController::class);
            Route::get('scores/review', [ScoreReviewController::class, 'index']);
            Route::post('scores/{score}/approve', [ScoreReviewController::class, 'approve']);
            Route::post('events/{event}/scores/approve-all', [ScoreReviewController::class, 'approveAll']);
            Route::delete('events/{event}/scores/delete-all', [ScoreReviewController::class, 'deleteAll']);
            Route::delete('scores/{score}', [ScoreReviewController::class, 'destroy']);
            Route::get('results/{event}', [ResultController::class, 'index']);
        });

        // Organizer + Super Admin
        Route::middleware('role:super_admin,organizer')->prefix('organizer')->group(function (): void {
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('categories.criteria', CriterionController::class);
            Route::get('events/{event}/progress', [ProgressController::class, 'index']);
            Route::post('events/{event}/open-scoring', [ProgressController::class, 'openScoring']);
            Route::get('events/{event}/results', [ResultController::class, 'index']);
        });

        // Judge
        Route::middleware('role:admin')->prefix('judge')->group(function (): void {
            Route::get('events/{event}/scoresheet', [ScoreController::class, 'scoresheet']);
            Route::post('scores', [ScoreController::class, 'store']);
            Route::put('scores/{score}', [ScoreController::class, 'update']);
            Route::post('events/{event}/scores/submit', [ScoreController::class, 'submitAll']);
            Route::get('events/{event}/my-scores', [ScoreController::class, 'myScores']);
        });

        // MC
        Route::middleware('role:mc')->prefix('mc')->group(function (): void {
            Route::get('results', [ResultRevealController::class, 'index']);
            Route::post('results/reveal', [ResultRevealController::class, 'reveal']);
            Route::get('events/{event}/results', [ResultRevealController::class, 'indexForEvent']);
            Route::post('events/{event}/results/reveal', [ResultRevealController::class, 'revealForEvent']);
        });
    });
});
