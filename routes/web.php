<?php

use App\Http\Controllers\Drive\DriveController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('drive', [DriveController::class, 'index'])->name('drive.index');
    Route::post('drive/folders', [DriveController::class, 'storeFolder'])->name('drive.folders.store');
    Route::post('drive/files', [DriveController::class, 'storeFile'])->name('drive.files.store');
    Route::get('drive/files/{file}/download', [DriveController::class, 'download'])->name('drive.files.download');
    Route::delete('drive/folders/{folder}', [DriveController::class, 'destroyFolder'])->name('drive.folders.destroy');
    Route::delete('drive/files/{file}', [DriveController::class, 'destroyFile'])->name('drive.files.destroy');
    Route::post('drive/folders/{folder}/share', [DriveController::class, 'shareFolder'])->name('drive.folders.share');
    Route::post('drive/files/{file}/share', [DriveController::class, 'shareFile'])->name('drive.files.share');
    Route::delete('drive/shares/{share}', [DriveController::class, 'revokeShare'])->name('drive.shares.destroy');
});

require __DIR__.'/settings.php';
