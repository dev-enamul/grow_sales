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
        Schema::create('hr_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            // Settings
            $table->enum('weekend_type', ['shift_based', 'employee_based'])->default('shift_based')->comment('Determines if weekend is based on shift or individual employee');
            $table->boolean('salary_deduct_on_absent')->default(true);
            $table->enum('absent_fine_type', ['fixed', 'per_day'])->default('per_day');
            $table->decimal('absent_fine_amount', 10, 2)->nullable(); // If fixed
            $table->boolean('late_fine_enabled')->default(false);
            $table->json('config')->nullable(); // General settings
            
            // Standard tracking fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_settings');
    }
};
