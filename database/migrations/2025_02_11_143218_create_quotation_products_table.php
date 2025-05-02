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
        Schema::create('quotation_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations');  
            $table->foreignId('product_id')->constrained('products');  
            $table->decimal('original_price', 10, 2)->default(0);  
            $table->decimal('sell_price', 10, 2)->default(0); 
            $table->integer('quantity')->default(1); 
            $table->decimal('vat_percent', 10, 2)->default(0); 
            $table->decimal('vat_amount', 10, 2)->default(0); 
            $table->decimal('discount', 10, 2)->default(0);  
            $table->enum('discount_type', ['flat', 'percentage'])->default('flat');  
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_products');
    }
};
