<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Middleware\RateLimiter;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post(
    '/dispatch',
    [App\Http\Controllers\DispatchController::class, 'store']
)->middleware(RateLimiter::class); // constructor is called automatically
Route::get('/dispatch/{dispatch}', [App\Http\Controllers\DispatchController::class, 'show']);
