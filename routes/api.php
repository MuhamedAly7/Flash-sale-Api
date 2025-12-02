<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\HoldController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

Route::get('/test', function () {
    return ApiResponse::success('API is working');
});

Route::post('/hold', [HoldController::class, 'hold']);
Route::post('/order', [OrderController::class, 'order']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/webhook/payment-success', [WebhookController::class, 'paymentSuccess']);

