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
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            // If designation_id is NULL, it applies to all employees (Global Rule)
            $table->unsignedBigInteger('designation_id')->nullable();
            
            $table->integer('late_days_count')->default(3); // e.g. 3 days late
            $table->decimal('deduction_day_count', 5, 2)->default(1); // e.g. 1 day salary cut
            
            $table->enum('deduction_amount_type', ['basic_salary', 'gross_salary', 'fixed_amount'])->default('basic_salary');
            $table->decimal('fixed_amount', 10, 2)->nullable(); // Used if deduction_amount_type is 'fixed_amount'
            
            $table->boolean('is_active')->default(true);
            $table->json('rules')->nullable(); // For flexible rules

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
        Schema::dropIfExists('attendance_policies');
    }
};
