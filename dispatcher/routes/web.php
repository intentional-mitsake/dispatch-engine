<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\StartWorkers;

Route::get('/', fn() => view('index'));