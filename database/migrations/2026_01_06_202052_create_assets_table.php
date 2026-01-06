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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->comment('Laptop, Furniture, Vehicle');
            $table->date('purchase_date')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            
            $table->enum('status', ['available', 'assigned', 'damaged', 'lost', 'sold'])->default('available');
            
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
        Schema::dropIfExists('assets');
    }
};
