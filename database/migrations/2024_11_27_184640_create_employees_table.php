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
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())')); 
            $table->foreignId('user_id')->constrained();
            $table->string('employee_id')->unique();  
            $table->string('signature')->nullable(); 
        
            $table->foreignId('ref_id')->nullable()->constrained('users');  
        
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive'); 
            $table->boolean('is_resigned')->default(false)->comment('false=Not Resigned, true=Resigned');
            $table->date('resignation_date')->nullable()->comment('Date of resignation');
        
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
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
