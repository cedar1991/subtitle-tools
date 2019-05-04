<?php

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\SubIdxBatchController;
use App\Http\Controllers\User\SubIdxBatchLinkedFilesController;
use App\Http\Controllers\User\SubIdxBatchUnlinkedFilesController;
use App\Http\Controllers\User\SubIdxBatchUploadController;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

Route::get('/sub-idx-batches', [SubIdxBatchController::class, 'index'])->name('subIdxBatch.index');
Route::get('/sub-idx-batches/create', [SubIdxBatchController::class, 'create'])->name('subIdxBatch.create');
Route::post('/sub-idx-batches/create', [SubIdxBatchController::class, 'store'])->name('subIdxBatch.store');

Route::get('/sub-idx-batches/{subIdxBatch}', [SubIdxBatchUploadController::class, 'index'])->name('subIdxBatch.showUpload')->middleware('can:access,subIdxBatch');
Route::post('/sub-idx-batches/{subIdxBatch}/upload', [SubIdxBatchUploadController::class, 'post'])->name('subIdxBatch.upload')->middleware('can:access,subIdxBatch');

Route::get('/sub-idx-batches/{subIdxBatch}/unlinked', [SubIdxBatchUnlinkedFilesController::class, 'index'])->name('subIdxBatch.showUnlinked')->middleware('can:access,subIdxBatch');
Route::post('/sub-idx-batches/{subIdxBatch}/link', [SubIdxBatchUnlinkedFilesController::class, 'link'])->name('subIdxBatch.link')->middleware('can:access,subIdxBatch');

Route::get('/sub-idx-batches/{subIdxBatch}/files', [SubIdxBatchLinkedFilesController::class, 'index'])->name('subIdxBatch.showLinked')->middleware('can:access,subIdxBatch');
Route::post('/sub-idx-batches/unlink/{subIdxBatchFile}', [SubIdxBatchLinkedFilesController::class, 'unlink'])->name('subIdxBatch.unlink')->middleware('can:access,subIdxBatch');