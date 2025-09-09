<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BlogController;

Route::prefix('v1/auth')->group(function () {
    Route::post("register", [AuthController::class, "register"]);
    Route::post("login", [AuthController::class, "login"]);

    Route::middleware("auth:sanctum")->group(function () {
        Route::post("logout", [AuthController::class, "logout"]);
    });
});

Route::prefix('v1/blog')->group(function () {
    Route::get('read', [BlogController::class, 'index']);
    Route::get('show/{slug}', [BlogController::class, 'showBySlug']);
    Route::get('read/{id}', [BlogController::class, 'show']);

    Route::middleware("auth:sanctum")->group(function () {
        Route::post('create', [BlogController::class, 'store']);
        Route::put('update/{id}', [BlogController::class, 'update']);
        Route::delete('delete/{id}', [BlogController::class, 'delete']);
        Route::get('deleted', [BlogController::class, 'showDeleted']);
        Route::post('restore/{id}', [BlogController::class, 'restore']);
    });
});
