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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->enum('user_type', ['employee', 'affiliate', 'customer'])->nullable();
            $table->string('profile_image')->nullable();
            
            // Employee and Affiliate common fields
            $table->string('user_id')->nullable()->unique();
            $table->string('signature')->nullable();
            
            // Employee specific fields
            $table->boolean('is_admin')->default(false);
            $table->decimal('salary', 10, 2)->default(0);
            $table->boolean('is_resigned')->default(false)->comment('false=Not Resigned, true=Resigned');
            $table->date('resigned_at')->nullable()->comment('Date of resignation');
            
            // Referral information (used by both employee and affiliate)
            $table->foreignId('referred_by')->nullable()->constrained('users');
            
            // Status (used by both employee and affiliate)
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            
            $table->json('senior_user')->nullable(); 
            $table->json('junior_user')->nullable();

            $table->foreignId('role_id')->nullable()->constrained('roles');
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps(); 

            $table->unique(['company_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
