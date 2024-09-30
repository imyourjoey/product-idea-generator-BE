<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\RegisterController;


Route::get('/', function () {
    return view('welcome');
});

Route::get( '/unauthenticated', [RegisterController::class, 'unauthenticated'])->name('login');

