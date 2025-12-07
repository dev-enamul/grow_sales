<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{ 
    public function up(): void
    {
        Schema::create('lead_products', function (Blueprint $table) {
            $table->id(); 
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->string('type')->nullable(); 
            $table->foreignId('property_unit_id')->nullable()->constrained('product_units')->onDelete('set null');
            $table->foreignId('area_id')->nullable()->constrained('areas')->onDelete('set null');
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            $table->foreignId('product_sub_category_id')->nullable()->constrained('product_sub_categories')->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
           
            
            
            $table->integer('qty')->nullable(); // for quotation
            $table->decimal('price', 10, 2)->nullable(); // for quotation
            $table->decimal('subtotal', 10, 2)->nullable(); // for quotation
            $table->float('vat_rate')->nullable(); // for quotation
            $table->decimal('vat_value', 10, 2)->nullable(); // for quotation
            $table->decimal('discount', 10, 2)->nullable(); // for quotation
            $table->decimal('grand_total', 10, 2)->nullable(); // for quotation
            $table->decimal('negotiated_price', 10, 2)->nullable(); // for negotiation 

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
        Schema::dropIfExists('lead_products');
    }
};
