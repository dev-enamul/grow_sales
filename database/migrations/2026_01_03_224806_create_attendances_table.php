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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id'); // Employee
            
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->date('date');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            
            // Calculations
            $table->integer('work_minutes')->default(0); 
            $table->integer('overtime_minutes')->default(0);
            $table->boolean('is_late')->default(false);
            $table->text('late_reason')->nullable();
            $table->boolean('is_manual_entry')->default(false);
            
            // Enum status
            $table->enum('status', ['present', 'absent', 'late', 'leave', 'holiday', 'weekend'])->default('absent');
            
            $table->string('ip_address')->nullable();
            $table->text('location')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexing for faster query
            $table->index(['company_id', 'user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
