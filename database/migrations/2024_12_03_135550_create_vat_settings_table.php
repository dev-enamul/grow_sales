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
        Schema::create('vat_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())')); 
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); 
            $table->decimal('vat_percentage', 5, 2);  
            $table->boolean('is_active')->default(true);  
            $table->text('note')->nullable();  
            
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
        Schema::dropIfExists('vat_settings');
    }
};
