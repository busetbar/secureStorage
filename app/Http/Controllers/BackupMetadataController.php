<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
            'duration_encrypt_ms'  => $request->duration_ms,
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

    /**
     * STEP 4:
     * DELETE BACKUP (hapus file di MinIO + hapus di database)
     */
    public function delete($id)
    {
        $backup = Backup::find($id);

        if (! $backup) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // Jika tidak ada path MinIO, langsung hapus metadata
        if (! $backup->path) {
            $backup->delete();
            return response()->json(['deleted' => true, 'minio' => false]);
        }

        // --- 1. Hapus file di GO WORKER ---
        $goUrl = "http://192.168.200.211:9090/delete?path={$backup->path}";

        $response = Http::timeout(10)->delete($goUrl);

        if (! $response->ok()) {
            return response()->json([
                'error' => 'Failed to delete file in worker',
                'worker_response' => $response->json()
            ], 500);
        }

        // --- 2. Hapus metadata di database ---
        $backup->delete();

        return response()->json([
            'deleted' => true,
            'minio' => true
        ]);
    }

    public function checkDecryptTime($id)
    {
        $backup = Backup::find($id);

        if (! $backup || ! $backup->path) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $url = "http://192.168.200.211:9090/integrity?path={$backup->path}";

        $response = Http::timeout(60)->get($url);

        if (! $response->ok()) {
            return response()->json(['error' => 'Failed integrity check'], 500);
        }

        $backup->duration_decrypt_ms = $response->json()['time_ms'];
        $backup->save();

        return response()->json([
            'decrypt_duration_ms' => $backup->duration_decrypt_ms
        ]);
    }
}
