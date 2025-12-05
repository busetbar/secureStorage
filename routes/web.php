<?php

use App\Filament\Pages\BackupDetails;
use App\Http\Controllers\BackupMetadataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::post('/backup/metadata', [BackupMetadataController::class, 'store'])
//     ->name('backup.metadata.store')
//     ->middleware('auth');

Route::post('/backup/metadata', [BackupMetadataController::class, 'storeMetadata'])
    ->name('backup.metadata.store')
    ->middleware('auth');

Route::delete('/backup/{id}', [BackupMetadataController::class, 'delete'])
    ->name('backup.delete')->middleware('auth');

    Route::get('/admin/backups/{id}/details', BackupDetails::class)
    ->name('backup.details');
