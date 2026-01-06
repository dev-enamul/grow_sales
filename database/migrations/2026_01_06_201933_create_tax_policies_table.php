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
        Schema::create('tax_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            
            $table->string('fiscal_year')->comment('e.g. 2025-2026');
            $table->decimal('min_amount', 12, 2);
            $table->decimal('max_amount', 12, 2)->nullable()->comment('Null means above');
            $table->decimal('percentage', 5, 2);
            $table->enum('gender', ['male', 'female', 'common'])->default('common');
            
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
        Schema::dropIfExists('tax_configs');
    }
};
