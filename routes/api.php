<?php

use App\Http\Controllers\API\AiMessageLogController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\GeneratedProductIdeaController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\PromptController;
use App\Http\Controllers\API\RegisterController;
use Illuminate\Support\Facades\Route;

Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('products', ProductController::class);
    Route::resource('brands', BrandController::class);
    Route::post('/prompt', [PromptController::class, 'handlePrompt']);
    Route::post('/ask-ai', [PromptController::class, 'handleAskAi']);
    Route::resource('generated-product-ideas', GeneratedProductIdeaController::class);
    Route::apiResource('ai-message-logs', AiMessageLogController::class);
    Route::get('/generated-product-ideas/{id}/ai-message-logs', [AiMessageLogController::class, 'index']);
});
