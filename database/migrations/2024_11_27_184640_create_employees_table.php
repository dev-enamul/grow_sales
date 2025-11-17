<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
    
            // User-related columns
            $table->foreignId('user_id')->constrained(); 
            $table->string('employee_id')->unique(); 
            $table->string('signature')->nullable(); 
            $table->boolean('is_admin')->default(false);
             
        
            // Salary information
            $table->decimal('salary')->default(0);  
        
            // Referral information
            $table->foreignId('referred_by')->nullable()->constrained('users'); 
        
            // Employment status
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive'); 
            $table->boolean('is_resigned')->default(false)->comment('false=Not Resigned, true=Resigned'); 
            $table->date('resigned_at')->nullable()->comment('Date of resignation'); 
        
            // Audit columns (for created, updated, and deleted by users)
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
        
            // Soft deletes and timestamps
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
