<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\PromptController;


Route::controller(RegisterController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout');
});
         
Route::middleware('auth:sanctum')->group( function () {
    Route::resource('products', ProductController::class);
    Route::resource('brands', BrandController::class);
    Route::post('/prompt', [PromptController::class, 'handlePrompt']);

});



