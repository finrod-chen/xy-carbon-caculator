<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// 沒有公開註冊：帳號一律由管理員建立（PHASE0 WP0.4）
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::prefix('admin')->name('admin.')->middleware('can:viewAny,App\Models\User')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->middleware('can:create,App\Models\User')->name('users.create');
        Route::post('users', [UserController::class, 'store'])->middleware('can:create,App\Models\User')->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('can:update,user')->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('can:update,user')->name('users.update');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('can:update,user')->name('users.deactivate');
        Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->middleware('can:update,user')->name('users.reactivate');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('can:update,user')->name('users.reset-password');
    });
});
