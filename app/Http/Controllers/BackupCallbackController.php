<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;

class BackupCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->validate([
            'backup_id'     => 'required|integer',
            'filename'      => 'required|string',
            'minio_path'    => 'required|string',
            'original_size' => 'required|integer',
            'final_size'    => 'required|integer',
            'duration_ms'   => 'required|integer',
        ]);

        $backup = Backup::find($data['backup_id']);

        if (!$backup) {
            return response()->json(['error' => 'backup not found'], 404);
        }

        $backup->update([
            'path'          => $data['minio_path'],
            'stored_filename' => $data['minio_path'],
            'final_size'    => $data['final_size'],
            'status'        => 'completed',
            'duration_encrypt_ms'  => $data['duration_ms'],
        ]);

        return response()->json(['ok' => true]);
    }
}

