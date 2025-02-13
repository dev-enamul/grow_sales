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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();   
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())')); 
            $table->string('name');  
            $table->string('website')->nullable(); 
            $table->text('address')->nullable(); 
            $table->string('logo')->nullable();  
            $table->string('primary_color')->nullable(); 
            $table->string('secondary_color')->nullable();  
            $table->date('founded_date')->nullable();  
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
         
            $table->foreignId('category_id')->constrained('company_categories')->onDelete('cascade');
        
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
