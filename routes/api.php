<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware(['auth:sanctum', 'not-banned'])->group(function () {
    Route::get('/user', fn (Request $request) => new UserResource($request->user()))->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('/posts', [PostController::class, 'store'])->name('api.posts.store');
    Route::put('/posts/{id}', [PostController::class, 'update'])->name('api.posts.update');
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('api.posts.destroy');
});

Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
Route::get('/posts/{id}', [PostController::class, 'show'])->name('api.posts.show');