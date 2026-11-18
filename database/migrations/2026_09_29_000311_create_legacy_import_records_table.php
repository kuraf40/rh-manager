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
        Schema::create('legacy_import_records', function (Blueprint $table) {
            $table->id();
            $table->string('source_table', 64);
            $table->string('source_key', 100);
            $table->string('target_table', 64);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();
            $table->unique(['source_table', 'source_key']);
            $table->index(['target_table', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_import_records');
    }
};
