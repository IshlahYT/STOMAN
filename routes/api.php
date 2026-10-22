<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataTablesController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\ItemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes with JWT Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
});

// Read actions (accessible to both Admin and User roles)
Route::get('items', [ItemController::class, 'list'])->name('api.items.list');
Route::get('folders/{folder}', [FolderController::class, 'show'])->name('api.folders.show');
Route::get('files/{file}/download', [FileController::class, 'download'])->name('api.files.download');
Route::get('files/{file}/preview', [FileController::class, 'preview'])->name('api.files.preview');
Route::get('datatables/files', [DataTablesController::class, 'files'])->name('api.datatables.files');

// Admin-only mutation actions (Full CRUD operations for managing folders and files)
Route::middleware('admin')->group(function () {
    // Storage item actions
    Route::delete('items', [ItemController::class, 'destroy'])->name('api.items.destroy');
    Route::post('items/move', [ItemController::class, 'move'])->name('api.items.move');
    Route::post('items/archive', [ItemController::class, 'archive'])->name('api.items.archive');
    Route::post('items/restore', [ItemController::class, 'restore'])->name('api.items.restore');
    Route::post('items/trash/empty', [ItemController::class, 'emptyTrash'])->name('api.items.trash.empty');
    Route::patch('items/{id}', [ItemController::class, 'rename'])->name('api.items.rename');
    Route::patch('items/{id}/star', [ItemController::class, 'star'])->name('api.items.star');
    Route::post('items/{id}/shares', [ItemController::class, 'share'])->name('api.items.share');

    // Folder management
    Route::post('folders', [FolderController::class, 'store'])->name('api.folders.store');
    Route::patch('folders/{folder}', [FolderController::class, 'update'])->name('api.folders.update');
    Route::delete('folders/{folder}', [FolderController::class, 'destroy'])->name('api.folders.destroy');

    // File upload
    Route::post('files', [FileController::class, 'upload'])->name('api.files.upload');
});
