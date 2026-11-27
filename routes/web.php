<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\AcceptAnswerController;


Route::get('/', [PostController::class, 'index'])->name('posts.index');

Route::get('/about', function () {
    return 'About page';
});


Route::get('/dashboard', function (Request $request) {
    $lastPost = null;

    if ($id = $request->cookie('last_post')) {
        $lastPost = Post::published()->find($id);
    }

    return view('dashboard', ['lastPost' => $lastPost]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/admin/dashboard', function () {
    return 'Admin area';
})->middleware(['auth', 'can:manage-tags']);

// Posts: write actions require login. These must come BEFORE /posts/{id}
Route::middleware('auth')->group(function () {
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{id}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/posts/{id}/comments', [PostController::class, 'storeComment'])->name('comments.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Anyone can read a post
Route::get('/posts/{id}', [PostController::class, 'show'])->name('posts.show');

Route::get('/questions/{question}/{slug?}', [QuestionController::class, 'show'])
    ->whereNumber('question')
    ->name('questions.show');

Route::middleware(['auth', 'not-banned'])->group(function () {
    // posts/create, store, edit, update, destroy, comments, profile routes
    Route::get('/questions/create', [QuestionController::class, 'create'])->name('questions.create');

    Route::post('/questions', [QuestionController::class, 'store'])
        ->middleware('throttle:5,1,questions')
        ->name('questions.store');

    Route::post('/questions/{question}/answers', [AnswerController::class, 'store'])
        ->whereNumber('question')
        ->middleware('throttle:10,1,answers')
        ->name('questions.answers.store');

    Route::post('/questions/{question}/vote', [VoteController::class, 'question'])
        ->whereNumber('question')
        ->middleware('throttle:20,1,votes')
        ->name('questions.vote');

    Route::post('/answers/{answer}/vote', [VoteController::class, 'answer'])
        ->whereNumber('answer')
        ->middleware('throttle:20,1,votes')
        ->name('answers.vote'); 

    Route::post('/answers/{answer}/accept', AcceptAnswerController::class)
        ->whereNumber('answer')
        ->middleware('throttle:30,1,accept')
        ->name('answers.accept');    
});  

require __DIR__.'/auth.php';