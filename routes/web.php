<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RecentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SharedController;
use App\Http\Controllers\StarredController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// The website never bypasses authentication: opening "/" while logged out
// redirects straight to the Login page.
Route::get('/', [HomeController::class, 'index'])->middleware('auth')->name('home');

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'webLogin']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'webRegister']);
Route::post('/logout', [AuthController::class, 'webLogout'])->name('logout');

// Strict route protection: every application page requires an authenticated
// session, and regular users (role "User") may only access the Home (Dashboard)
// — any other URL redirects them back to home.
Route::middleware(['auth', 'admin:home'])->group(function () {
    Route::get('/storage', [StorageController::class, 'index'])->name('storage.index');
    Route::get('/storage/{folder}', [StorageController::class, 'folder'])
        ->where('folder', '[^/]+')
        ->name('storage.folder');

    Route::get('/recent', [RecentController::class, 'index'])->name('recent.index');
    Route::get('/starred', [StarredController::class, 'index'])->name('starred.index');
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::get('/shared', [SharedController::class, 'index'])->name('shared.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});

// User Management (restricted to Admin role only)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/user-management', [UserController::class, 'index'])->name('users.management');
    Route::get('/users-management', [UserController::class, 'index']);
    Route::get('/user', [UserController::class, 'index']);
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::get('/admin/user-management', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Error pages, also directly accessible for review/QA.
foreach (['403', '404', '500'] as $code) {
    Route::get('/errors/'.$code, fn () => response()->view('errors.'.$code, [], (int) $code))
        ->name('errors.'.$code);
}
