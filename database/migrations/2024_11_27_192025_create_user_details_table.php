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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); 
            $table->string('name')->nullable()->comment();
            $table->string('primary_phone', 20)->nullable();
            $table->string('secondary_phone', 20)->nullable();
            $table->string('primary_email', 45)->nullable();
            $table->string('secondary_email', 45)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('imo', 20)->nullable();
            $table->string('facebook', 100)->nullable();
            $table->string('linkedin', 100)->nullable();
            $table->string('website')->nullable();
            $table->date('dob')->nullable(); 
            $table->enum('gender', ['male', 'female', 'others'])->nullable(); 
            $table->enum('marital_status', ['married', 'unmarried', 'divorced'])->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->string('religion', 45)->nullable();
            $table->string('education', 45)->nullable();
            $table->string('profession', 45)->nullable();
            $table->string('relationship_or_role')->nullable()->comment("Customer/Father/Mother/Relative/OfficeSomeone");
            $table->boolean('is_decision_maker')->default(false);

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
        Schema::dropIfExists('user_contacts');
    }
};
