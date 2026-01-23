<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('duration_compress_ms')
                  ->nullable()
                  ->after('duration_encrypt_ms');
            $table->unsignedBigInteger('duration_decompress_ms')
                  ->nullable()
                  ->after('duration_decrypt_ms');

            $table->unsignedBigInteger('duration_total_ms')
                  ->nullable()
                  ->after('duration_decompress_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            //
            $table->dropColumn([
                'duration_compress_ms',
                'duration_decompress_ms',
                'duration_total_ms',
            ]);
        });
    }
};
