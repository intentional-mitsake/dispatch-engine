<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/dispatch', [App\Http\Controllers\DispatchController::class, 'store']);
Route::get('/dispatch/{dispatch}', [App\Http\Controllers\DispatchController::class, 'show']);
