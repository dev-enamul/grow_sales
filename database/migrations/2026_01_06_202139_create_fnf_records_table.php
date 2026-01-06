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
        Schema::create('fnf_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('resignation_id')->constrained('resignations')->onDelete('cascade');
            
            $table->decimal('total_payable', 12, 2)->default(0); // Salary, Leave Encashment, Gratuity
            $table->decimal('total_deduction', 12, 2)->default(0); // Asset Damage, Notice Pay
            $table->decimal('net_amount', 12, 2);
            
            $table->boolean('is_settled')->default(false);
            $table->date('settlement_date')->nullable();
            
            $table->text('remarks')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnf_records');
    }
};
