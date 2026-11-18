<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'rh', 'manager', 'employee'])->default('employee')->after('email');
            $table->foreignId('department_id')->nullable()->after('role')->constrained('departments')->nullOnDelete();
            $table->decimal('contract_hours_per_week', 5, 2)->default(40.00)->after('department_id');
            $table->decimal('hourly_rate', 10, 2)->default(0.00)->after('contract_hours_per_week');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['role', 'department_id', 'contract_hours_per_week', 'hourly_rate']);
        });
    }
};