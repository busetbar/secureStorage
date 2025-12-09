<?php

use App\Http\Controllers\BackupCallbackController;
use App\Http\Controllers\BackupMetadataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::post('/backup/callback', [BackupCallbackController::class, 'handle']);
Route::post('/backup/callback', [BackupMetadataController::class, 'callback'])
    ->middleware([]);
Route::get('/backup/list/json', [BackupMetadataController::class, 'listJson']);
Route::get('/backup/decrypt-time/{id}', [BackupMetadataController::class, 'checkDecryptTime']);

// Route::post('/backup/metadata', [BackupMetadataController::class, 'storeMetadata'])
//     ->name('backup.metadata.store');

Route::get('/backup/status/{id}', function ($id) {
    return \App\Models\Backup::where('id', $id)
        ->select('status')
        ->first();
});