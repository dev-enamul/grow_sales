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
        Schema::table('users', function (Blueprint $table) {
            // Add missing employee columns from employees table
            $table->foreignId('contact_id')->nullable()->after('company_id')->constrained('contacts')->onDelete('set null');
            $table->foreignId('shift_id')->nullable()->after('contact_id')->constrained('work_shifts')->onDelete('set null');
            $table->date('joining_date')->nullable()->after('shift_id');
            $table->json('weekend_days')->nullable()->after('joining_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['contact_id', 'shift_id', 'joining_date', 'weekend_days']);
        });
    }
};
