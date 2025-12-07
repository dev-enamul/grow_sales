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
        Schema::create('contact_addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade'); 
            $table->enum('address_type', ['permanent', 'present', 'billing', 'shipping', 'office', 'home', 'other'])->default('permanent'); 
            $table->foreignId('area_id')->nullable()->constrained('areas')->onDelete('set null'); 
            $table->string('postal_code', 20)->nullable();
            $table->string('address', 255)->nullable(); 
            $table->decimal('latitude', 10, 8)->nullable(); 
            $table->decimal('longitude', 11, 8)->nullable(); 
            $table->boolean('is_same_present_permanent')->nullable()->comment('0=Diff Address , 1  = Same Address'); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_addresses');
    }
};
