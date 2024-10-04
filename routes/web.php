<?php

use App\Http\Controllers\API\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/unauthenticated', [RegisterController::class, 'unauthenticated'])->name('login');
