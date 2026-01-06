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
        Schema::create('bonus_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->string('title'); // e.g., Eid Bonus, Festival Bonus
            $table->enum('type', ['fixed', 'percentage']);
            $table->decimal('value', 12, 2); // Amount or Percentage
            $table->enum('basis', ['basic', 'gross'])->default('basic');
            $table->integer('min_service_period_months')->default(0); // Eligible after X months
            $table->string('religion')->nullable(); // Optional filter
            $table->enum('gender', ['male', 'female', 'other'])->nullable(); // Optional filter
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_policies');
    }
};
