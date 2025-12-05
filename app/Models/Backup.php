<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'original_filename',
        'stored_filename',
        'path',
        'original_size',
        'final_size',
        'status',
        'user_id',
        'duration_encrypt_ms',
        'duration_decrypt_ms', // ← WAJIB ADA
    ];
}
