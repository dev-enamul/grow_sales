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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // User who created the quotation
            $table->foreignId('customer_id')->constrained('customers'); // Customer for this quotation
            $table->decimal('original_price', 10, 2)->default(0); // Total original price of the products in the quotation
            $table->decimal('sell_price', 10, 2)->default(0); // Total quoted price after discounts
            $table->decimal('tax_percent', 10, 2)->default(0); // Tax percentage
            $table->decimal('tax_amount', 10, 2)->default(0); // Tax amount
            $table->decimal('discount', 10, 2)->default(0); // Total discount applied on the quotation
            $table->decimal('total_amount', 10, 2)->default(0); // Final amount after discount and tax
            $table->date('quotation_date'); // Date of the quotation
            $table->foreignId('created_by')->constrained('users'); // Salesperson who created the quotation
            $table->tinyInteger('status')->comment('0 = Pending, 1 = Approved, 2 = Rejected'); // Quotation status
            $table->text('notes')->nullable(); // Additional notes about the quotation
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
