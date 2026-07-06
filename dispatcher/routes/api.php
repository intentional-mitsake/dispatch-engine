<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Middleware\RateLimiter;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\StatsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/index', [DispatchController::class, 'index']);

Route::post(
    '/dispatches',
    [DispatchController::class, 'store']
)->middleware(RateLimiter::class); // constructor is called automatically


Route::get('/dispatches', [DispatchController::class, 'index']);

Route::get('/dispatches/{dispatch}', [DispatchController::class, 'show']);

Route::get('/stats', [StatsController::class, 'index']);
