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
        Schema::table('registered_hours', function (Blueprint $table) {
            $table->string('vacation', 20)->nullable()->after('heure_fin');
            $table->string('type_jour', 20)->nullable()->after('vacation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registered_hours', function (Blueprint $table) {
            $table->dropColumn(['vacation', 'type_jour']);
        });
    }
};
