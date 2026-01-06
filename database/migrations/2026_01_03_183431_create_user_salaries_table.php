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
        Schema::create('user_salaries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->date('effective_date')->comment('When this salary starts');
            $table->date('end_date')->nullable()->after('effective_date')->comment('When this salary ends (NULL means current)');
            $table->boolean('is_active')->default(true)->comment('Only one active salary per user at a time');
            $table->string('increment_reason')->nullable()->comment('Initial, Annual Increment, Promotion, etc.');
            
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
        Schema::dropIfExists('user_salaries');
    }
};
