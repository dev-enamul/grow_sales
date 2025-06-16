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
        Schema::create('products', function (Blueprint $table) {
            $table->id(); 
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); 
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units');
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade'); 
            $table->foreignId('sub_category_id')->nullable()->constrained('pro_duct_sub_categories')->onDelete('set null');
            $table->string('name'); 
            $table->string('slug'); 
            $table->text('description')->nullable();
            $table->string('code')->nullable();

            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('unit')->default(0); 
            $table->decimal('total_price', 10, 2)->default(0);
            $table->foreignId('vat_setting_id')->nullable()->constrained('vat_settings');
            $table->integer('qty_in_stock')->nullable();
            $table->integer('floor')->nullable();
             
            $table->unsignedInteger('status')->default(1)->comment("1=Active, 0= UnActive");
           
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps(); 

            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
