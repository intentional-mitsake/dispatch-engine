<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/dispatch', [App\Http\Controllers\DispatchController::class, 'store']);
Route::get('/dispatch', [App\Http\Controllers\DispatchController::class, 'show']);