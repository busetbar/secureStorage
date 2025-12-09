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
            'original_sha256'    => $request->original_sha256, // wajib
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
     * Callback dari Go Worker setelah upload selesai
     */
    public function callback(Request $request)
    {
        Log::info("CALLBACK RAW", $request->all());

        // ============================
        // EVENT: UPLOAD
        // ============================
        if ($request->event === "upload") {

            $backup = Backup::find($request->backup_id);

            if (!$backup) {
                Log::warning("UPLOAD CALLBACK FAILED — BACKUP NOT FOUND ID={$request->backup_id}");
                return response()->json(['error' => 'not found'], 404);
            }

            $backup->update([
                'path'                => $request->minio_path,
                'stored_filename'     => basename($request->minio_path),
                'final_size'          => $request->final_size,
                'status'              => 'completed',
                'duration_encrypt_ms' => $request->duration_ms,
            ]);

            Log::info("UPLOAD CALLBACK UPDATED", $backup->toArray());

            return response()->json(['upload_callback' => true]);
        }

        // ============================
        // EVENT: INTEGRITY
        // ============================
        if ($request->event === "integrity") {

            // prefer by backup_id if provided
            $backup = null;
            if ($request->has('backup_id') && $request->backup_id) {
                $backup = Backup::find($request->backup_id);
                if (! $backup) {
                    Log::warning("INTEGRITY CALLBACK FAILED — BACKUP NOT FOUND ID={$request->backup_id}");
                    // fallback to path lookup below
                }   
            }

            // fallback: find by path
            if (! $backup) {
                if (! $request->path) {
                    Log::error("INTEGRITY CALLBACK ERROR — Missing path and no valid backup_id");
                    return response()->json(['error' => 'path or backup_id required'], 400);
                }

                $backup = Backup::where('path', $request->path)->first();
                if (! $backup) {
                    Log::warning("INTEGRITY CALLBACK FAILED — BACKUP NOT FOUND FOR PATH {$request->path}");
                    return response()->json(['error' => 'not found'], 404);
                }
            }

            // update
            $backup->after_sha256 = $request->hash_after;
            $backup->duration_decrypt_ms = $request->time_ms;
            $backup->integrity_passed = ($backup->original_sha256 === $request->hash_after);
            $backup->save();

            Log::info("INTEGRITY CALLBACK UPDATED", $backup->toArray());

            return response()->json(['integrity_callback' => true]);
        }
    }

    /**
     * STEP 3:
     * List backup
     */
    public function listJson()
    {
        return Backup::orderBy('id', 'desc')->get();
    }

    /**
     * STEP 4:
     * DELETE
     */
    public function delete($id)
    {
        $backup = Backup::find($id);

        if (! $backup) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (! $backup->path) {
            $backup->delete();
            return response()->json(['deleted' => true, 'minio' => false]);
        }

        $goUrl = "http://192.168.200.211:9090/delete?path={$backup->path}";
        $response = Http::timeout(10)->delete($goUrl);

        if (! $response->ok()) {
            return response()->json([
                'error' => 'Failed to delete file in worker',
                'worker_response' => $response->json()
            ], 500);
        }

        $backup->delete();

        return response()->json([
            'deleted' => true,
            'minio' => true,
        ]);
    }

    /**
     * STEP 5:
     * Integrity Check — decrypt + decompress + SHA-256
     */
    public function checkDecryptTime($id)
    {
        $backup = Backup::find($id);

        if (!$backup || !$backup->path) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $url = "http://192.168.200.211:9090/integrity?path={$backup->path}&backup_id={$backup->id}";
        $response = Http::timeout(120)->get($url);

        if (!$response->ok()) {
            return response()->json(['error' => 'Integrity check failed'], 500);
        }

        $afterHash = $response->json()['hash_after'];
        $timeMs    = $response->json()['time_ms'];

        $backup->after_sha256 = $afterHash;
        $backup->duration_decrypt_ms = $timeMs;
        $backup->integrity_passed = ($backup->original_sha256 === $afterHash);
        $backup->save();

        return response()->json([
            'hash_after'        => $afterHash,
            'integrity_passed'  => $backup->integrity_passed,
            'time_ms'           => $timeMs,
        ]);
    }
}
