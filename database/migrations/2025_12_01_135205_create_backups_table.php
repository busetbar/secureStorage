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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('original_filename');
            $table->string('stored_filename')->nullable();
            $table->string('path'); // MinIO path
            $table->unsignedBigInteger('original_size')->nullable();
            $table->unsignedBigInteger('final_size')->nullable();
            $table->string('status')->default('processing'); // processing | completed | failed
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
