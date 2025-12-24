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
        Schema::create('sales_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));

            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_reason_id')->constrained()->onDelete('cascade');

            $table->decimal('amount', 14, 2);           // Planned/Due amount
            $table->date('due_date');                   // payment_date → due_date
            $table->string('notes')->nullable();

            // Audit + soft deletes
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
        Schema::dropIfExists('sales_payment_schedules');
    }
};
