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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
        
            // Relations
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('lead_id')->constrained('leads'); 
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('set null');
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('set null'); 

            $table->enum('sale_type', ['sell', 'transfer'])->default('sell');
            $table->foreignId('sales_by')->nullable()->constrained('users')->onDelete('set null'); 
        
            // Amounts
            $table->decimal('subtotal', 10, 2)->nullable(); 
            $table->decimal('discount', 10, 2)->nullable(); 
            $table->decimal('grand_total', 10, 2)->nullable(); 
            $table->decimal('paid', 10, 2)->default(0);
            $table->decimal('due', 10, 2)->nullable();
            $table->decimal('refunded', 10, 2)->nullable();
            $table->decimal('transfer', 10, 2)->nullable();

        
            // Dates
            $table->date('sale_date');
            $table->date('delivery_date')->nullable();
        
            // Return handling 
            $table->text('return_reason')->nullable();
            $table->date('return_date')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users');
        
            // Transfer handling 
            $table->foreignId('child_sale_id')->nullable()->constrained('sales'); 
            $table->foreignId('transfer_by')->nullable()->constrained('users');
            $table->date('transfer_date')->nullable();
            $table->text('transfer_notes')->nullable(); 
        
            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'handover',
                'return',
                'transfer'
            ])->default('pending');  
        
            // Audits
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
        Schema::dropIfExists('sales');
    }
};
