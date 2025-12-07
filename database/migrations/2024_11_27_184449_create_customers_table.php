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
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); 
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade'); 
            $table->string('customer_code')->unique()->nullable()->comment("CUS-001"); 
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->foreignId('primary_contact_id')->nullable()->constrained('contacts')->onDelete('set null');
            $table->foreignId('referred_by')->nullable()->constrained('users');
        
            $table->integer('total_sales')->default(0)->nullable()->comment("Total Products Sold");
            $table->decimal('total_sales_amount', 15, 2)->default(0)->nullable()->comment("Total Amount of Sales"); 
        
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
        Schema::dropIfExists('customers');
    }
};
