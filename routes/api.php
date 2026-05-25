<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('throttle:strict')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum']);
});

Route::prefix('v1')->group(function () {
    require __DIR__.'/Versioning/v1_api.php';
});

Route::prefix('v2')->group(function () {
    require __DIR__.'/Versioning/v2_api.php';
});
