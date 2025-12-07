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
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())')); 
            $table->foreignId('company_id')->constrained()->onDelete('cascade');   
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('lead_categorie_id')->nullable()->constrained('lead_categories')->onDelete('set null');   
            $table->date('next_followup_date')->nullable();
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
        Schema::dropIfExists('followups');
    }
};
