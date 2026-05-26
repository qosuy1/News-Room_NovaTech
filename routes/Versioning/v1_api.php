<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Support\Facades\Route;


Route::get('/' , [HomeController::class , 'index']);

Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::get('/{article}', [ArticleController::class, 'show'])
        ->middleware('authorize.article:view');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ArticleController::class, 'store']);
        Route::put('/{article}', [ArticleController::class, 'update'])
            ->middleware('authorize.article:update');
        Route::delete('/{article}', [ArticleController::class, 'destroy'])
            ->middleware('authorize.article:delete');
        Route::patch('/{article}/publish', [ArticleController::class, 'publish'])
            ->middleware('authorize.article:publish');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
});
// tags
Route::prefix('tags')->group(function () {
    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store'])
        ->middleware(['auth:sanctum', 'authorize:admin', 'throttle:strict']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'authorize:admin', 'throttle:strict']);
});
