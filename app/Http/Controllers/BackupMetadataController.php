<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BackupMetadataController extends Controller
{
    /**
     * STEP 1:
     * Laravel menerima metadata sebelum upload dimulai
     */
    public function storeMetadata(Request $request)
    {
        $backup = Backup::create([
            'name'               => $request->name,
            'original_filename'  => $request->original_filename,
            'original_size'      => $request->original_size,
            
            // default values
            'path'               => null,
            'stored_filename'    => null,
            'final_size'         => null,
            'status'             => 'uploading',

            'user_id'            => Auth()->id(),
        ]);

        return response()->json([
            'backup_id' => $backup->id,
        ]);
    }

    /**
     * STEP 2:
     * Go Worker mengirim callback setelah upload selesai
     */
    public function callback(Request $request)
    {
         Log::info('CALLBACK RAW', $request->all());

        $backup = Backup::find($request->backup_id);

        if (! $backup) {
            Log::warning("BACKUP NOT FOUND ID=" . $request->backup_id);
            return response()->json(['error' => 'Not found'], 404);
        }

        $backup->update([
            'path'            => $request->minio_path,
            'stored_filename' => basename($request->minio_path),
            'final_size'      => $request->final_size,
            'status'          => 'completed',
        ]);

        Log::info('BACKUP UPDATED', $backup->toArray());

        return response()->json(['success' => true]);
    }

    /**
     * STEP 3:
     * List backup untuk frontend
     */
    public function listJson()
    {
        return Backup::orderBy('id', 'desc')->get();
    }
}
