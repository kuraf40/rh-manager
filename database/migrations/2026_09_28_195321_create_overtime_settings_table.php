<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('overtime_settings', function (Blueprint $table) {
            $table->id();
            $table->time('day_start_time')->default('06:00:00');
            $table->time('day_end_time')->default('21:00:00');

            $table->decimal('rate_day_tier1', 5, 2)->default(15.00);
            $table->decimal('rate_day_tier2', 5, 2)->default(50.00);
            $table->decimal('rate_night', 5, 2)->default(50.00);
            $table->decimal('rate_weekend_day', 5, 2)->default(50.00);
            $table->decimal('rate_weekend_night', 5, 2)->default(100.00);

            $table->decimal('tier1_max_hours', 5, 2)->default(8.00);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_settings');
    }
};