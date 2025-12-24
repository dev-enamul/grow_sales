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
        Schema::create('sales_users', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('sales_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Commission fields
            $table->enum('commission_type', ['percentage', 'amount'])->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(0)->comment('Percentage or fixed amount based on commission_type');
            $table->decimal('commission', 10, 2)->default(0)->comment('Calculated commission amount');
            
            // Payable commission
            $table->decimal('payable_commission', 10, 2)->default(0)->comment('Total commission that should be paid'); 
            
            // Paid commission
            $table->decimal('paid_commission', 10, 2)->default(0)->comment('Commission amount already paid');
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
            
            $table->timestamps();
            
            // Unique constraint to prevent duplicate entries
            $table->unique(['sales_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_users');
    }
};
