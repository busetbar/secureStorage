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
        //
        Schema::table('backups', function (Blueprint $table) {
        $table->string('path')->nullable()->change();
        $table->string('stored_filename')->nullable()->change();
        $table->bigInteger('final_size')->nullable()->change();
    });
}

public function down()
{
    Schema::table('backups', function (Blueprint $table) {
        $table->string('path')->nullable(false)->change();
        $table->string('stored_filename')->nullable(false)->change();
        $table->bigInteger('final_size')->nullable(false)->change();
    });
    }
};
