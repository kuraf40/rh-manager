<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timeslots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('is_holiday')->default(false);
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->timestamps();
        });

        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('timeslot_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('hours_tier1', 5, 2)->default(0);
            $table->decimal('hours_tier2', 5, 2)->default(0);
            $table->decimal('hours_night', 5, 2)->default(0);
            $table->decimal('hours_weekend_day', 5, 2)->default(0);
            $table->decimal('hours_weekend_night', 5, 2)->default(0);

            $table->decimal('total_hours', 5, 2);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtimes');
        Schema::dropIfExists('timeslots');
    }
};