<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    // public
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('login');
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{id}', [PostController::class, 'show'])->name('posts.show');

    // authenticated, not banned
    Route::middleware(['auth:sanctum', 'not-banned'])->group(function () {
        Route::get('/user', fn (Request $request) => new UserResource($request->user()))->name('user');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // needs the posts:write ability
        Route::middleware('abilities:posts:write')->group(function () {
            Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
            Route::put('/posts/{id}', [PostController::class, 'update'])->name('posts.update');
            Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
        });
    });
});