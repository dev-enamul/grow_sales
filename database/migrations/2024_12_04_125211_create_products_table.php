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
        $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
        $table->foreignId('sub_category_id')->nullable()->constrained('product_sub_categories')->onDelete('set null');

        $table->string('name');
        $table->string('slug');
        $table->text('description')->nullable();
        $table->string('code')->nullable();
        $table->foreignId('image')->nullable()->constrained('files')->onDelete('set null');

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
 
        $table->integer('qty_in_stock')->nullable();
        $table->integer('floor')->nullable();

        $table->unsignedTinyInteger('status')->default(0)->comment('0 = Unsold, 1 = Sold, 2 = Booked, 3 = Damaged, 4 = Returned');
        $table->string('applies_to');
        
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
