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
        Schema::create('ot_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('designation_id'); 
            
            $table->boolean('is_ot_allowed')->default(false);
            $table->enum('ot_rate_type', ['fixed_hourly', 'calculated_on_basic'])->default('calculated_on_basic');
            $table->decimal('ot_multiplier', 5, 2)->default(1.0); // e.g., 1.5x
            $table->decimal('ot_rate_fixed', 10, 2)->nullable(); // if fixed_hourly
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designation_ot_policies');
    }
};
