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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            
            $table->string('name'); // Sick, Casual
            $table->boolean('is_paid')->default(true);
            $table->integer('days_allowed')->default(10); // Yearly quota
            
            // Carry Forward & Encashment Settings
            $table->boolean('is_carry_forward')->default(false)->comment('Can be carried to next year?');
            $table->integer('max_carry_forward_days')->default(0)->comment('Max days to carry forward');
            $table->boolean('is_encashable')->default(false)->comment('Can be exchanged for money?');
            $table->json('policy_rules')->nullable(); // For complex logic
            
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
        Schema::dropIfExists('leave_types');
    }
};
