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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('lead_id')->nullable()->unique();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('set null');
              
            $table->foreignId('lead_category_id')->nullable()->constrained('lead_categories')->onDelete('set null');
            $table->date('next_followup_date')->nullable();
            $table->timestamp('last_contacted_at')->nullable();

            $table->decimal('subtotal', 10, 2)->nullable(); // for quotation 
            $table->decimal('discount', 10, 2)->nullable(); // for quotation
            $table->decimal('grand_total', 10, 2)->nullable(); // for quotation
            $table->decimal('negotiated_price', 10, 2)->nullable(); // for negotiation           
            
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); 
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->onDelete('set null');
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('set null');
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->onDelete('set null');
            $table->enum('status', ['Active', 'Rejected', 'Salsed', 'Waiting','Returned'])->default('Active');
            $table->text('notes')->nullable();
            $table->json('challenges')->nullable()->comment('json array of challenge ids');
            
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
        Schema::dropIfExists('leads');
    }
};
