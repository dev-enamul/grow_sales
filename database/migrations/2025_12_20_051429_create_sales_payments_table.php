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
        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));

            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_schedule_id')->nullable()->constrained('sales_payment_schedules')->onDelete('set null');
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete(); 

            // Actual received amount
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');

            $table->string('transaction_ref')->nullable(); // cheque no, bank ref
            $table->string('notes')->nullable();

            // Approval workflow (যদি লাগে)
            $table->tinyInteger('status')->default(1)->comment('0=Pending, 1=Approved, 2=Rejected');

            // Link to accounting entry
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Audit
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
        Schema::dropIfExists('sales_payments');
    }
};
