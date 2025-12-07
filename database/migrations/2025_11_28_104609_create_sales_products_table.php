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
        Schema::create('sales_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->integer('qty')->nullable(); // for quotation
            $table->decimal('price', 10, 2)->nullable(); // for quotation
            $table->decimal('subtotal', 10, 2)->nullable(); // for quotation
            $table->float('vat_rate')->nullable(); // for quotation
            $table->decimal('vat_value', 10, 2)->nullable(); // for quotation
            $table->decimal('discount', 10, 2)->nullable(); // for quotation
            $table->decimal('grand_total', 10, 2)->nullable(); // for quotation 

            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_products');
    }
};
