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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id'); 
            $table->enum('address_type', ['permanent', 'present']); 
            $table->string('country')->nullable();
            $table->string('division')->nullable();
            $table->string('district')->nullable();
            $table->string('upazila_or_thana')->nullable(); 
            $table->string('zip_code')->nullable(); 
            $table->string('region')->nullable(); //for international address
            $table->string('city')->nullable(); //for international address
            $table->text('address')->nullable();  

            $table->boolean('is_same_present_permanent')->nullable()->comment('0=Diff Address , 1  = Same Address');
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
