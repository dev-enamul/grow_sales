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
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('lead_id')->nullable()->unique();
            
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('lead_categorie_id')->constrained('lead_categories'); 
            $table->integer('purchase_probability')->nullable()->default(null)->comment("0-100%");
            $table->decimal('price')->nullable()->default(null);
            $table->date('next_followup_date')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); 
            $table->enum('status', ['Active', 'Rejected', 'Salsed', 'Waiting'])->default('Active');
            
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
