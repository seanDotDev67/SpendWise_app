<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::view('/transactions/page', 'transactions.index')->name('transactions.page');
    Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'update', 'destroy']);
});

Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});
require __DIR__.'/auth.php';

use App\Http\Controllers\PasswordCodeController;

Route::middleware('guest')->group(function () {
    Route::post('/forgot-password/send', [PasswordCodeController::class, 'send'])->middleware('throttle:5,1')->name('password.code.send');
    Route::get('/verify-code', [PasswordCodeController::class, 'showVerify'])->name('password.code.verify.show');
    Route::post('/verify-code', [PasswordCodeController::class, 'verify'])->middleware('throttle:10,1')->name('password.code.verify');
    Route::post('/verify-code/resend', [PasswordCodeController::class, 'resend'])->middleware('throttle:3,1')->name('password.code.resend');
    Route::get('/create-new-password', [PasswordCodeController::class, 'showReset'])->name('password.code.reset.show');
    Route::post('/create-new-password', [PasswordCodeController::class, 'reset'])->name('password.code.reset');
});
