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
            
            $table->decimal('rate', 15, 2)->default(0);     
            $table->integer('quantity')->default(0);      
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('other_price', 15, 2)->nullable();
            $table->float('discount')->default(0); 

            $table->foreignId('vat_setting_id')
                    ->nullable()
                    ->constrained('vat_settings')
                    ->onDelete('set null');
            $table->float('vat_rate')->nullable();
            $table->decimal('vat_amount', 15, 2)->nullable();
            $table->decimal('sell_price', 10, 2)->nullable(); 

            $table->integer('order_quantity')->default(0);
            $table->decimal('order_price', 10, 2)->default(0);
            $table->decimal('order_other_price', 10, 2)->default(0); 
            $table->float('order_discount')->default(0); 
            $table->decimal('order_total_price', 10, 2)->default(0);

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
