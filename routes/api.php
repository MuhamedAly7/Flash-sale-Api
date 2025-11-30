<?php

use App\Helpers\ApiResponse;
use App\Http\Controllers\HoldController;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

Route::get('/test', function () {
    return ApiResponse::success('API is working', [], 200);
});

Route::post('/hold', [HoldController::class, 'hold']);
