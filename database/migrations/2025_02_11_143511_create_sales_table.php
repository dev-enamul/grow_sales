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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');  
            $table->foreignId('customer_id')->constrained('customers'); 
            $table->decimal('original_price', 10, 2)->default(0); 
            $table->decimal('sell_price', 10, 2)->default(0);  
            $table->decimal('tax_percent', 10, 2)->default(0);  
            $table->decimal('tax_amount', 10, 2)->default(0);  
            $table->decimal('discount', 10, 2)->default(0); 
            $table->decimal('paid', 10, 2)->default(0);  
            $table->boolean('is_full_paid')->default(false);  
            $table->date('sale_date');  
            $table->foreignId('sales_by')->constrained('users'); 
            $table->integer('deal_type')->default(1)->comment('1= New customer, 2= Repeat, 3= Upsell'); 
            $table->text('notes')->nullable();  
            $table->tinyInteger('status')->comment('0= Quotation, 1= Sale, 2= Reject'); 
            $table->foreignId('quotation_id')->nullable()->constrained('quotations');  
            $table->decimal('total_amount', 10, 2)->default(0);  
            $table->enum('discount_type', ['flat', 'percentage'])->default('flat'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
