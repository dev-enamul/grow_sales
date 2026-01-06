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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            
            $table->integer('month'); // 1-12
            $table->integer('year'); // 2024
            
            $table->decimal('total_allowances', 12, 2)->default(0); // Sum of earnings (Including OT, Bonus)
            $table->decimal('total_deductions', 12, 2)->default(0); // Sum of deductions (Including Tax, Loan, Fine)
            
            $table->decimal('net_salary', 12, 2); // Final Payable
            
            $table->enum('status', ['draft', 'processed', 'paid'])->default('draft');
            $table->enum('payment_method', ['cash', 'bank', 'check'])->nullable();
            $table->date('payment_date')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
