<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/chat', [ChatController::class, 'index']);
    Route::get('/messages/{userId}', [ChatController::class, 'loadMessages']);
    Route::post('/messages/send', [ChatController::class, 'sendMessage']);
    Route::get('/messages/long-polling/{userId}', [ChatController::class, 'longPolling']);
    Route::post('/messages/mark-as-read/{messageId}', [ChatController::class, 'markAsRead']);
});

Route::get("/logout", [ProfileController::class, 'destroy']);


require __DIR__.'/auth.php';
