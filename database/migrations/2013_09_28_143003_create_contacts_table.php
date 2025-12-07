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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); 
            $table->string('name')->nullable()->comment();
            $table->string('phone', 20)->nullable();
            $table->foreignId('profile_image')->nullable()->constrained('files')->onDelete('set null');
            $table->string('secondary_phone', 20)->nullable();
            $table->string('email', 45)->nullable();
            $table->string('secondary_email', 45)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('facebook', 100)->nullable();
            $table->string('linkedin', 100)->nullable();
            $table->string('website')->nullable();
            $table->date('dob')->nullable(); 
            $table->enum('gender', ['Male', 'Female', 'Others'])->nullable(); 
            $table->enum('marital_status', ['Married', 'Unmarried', 'Divorced'])->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->string('religion', 45)->nullable();
            $table->string('education', 45)->nullable();
            $table->string('profession', 45)->nullable();
            $table->time('avalable_time')->nullable();
            $table->text('bio')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();  
        });

        // Add foreign key constraint for primary_contact_id in organizations table
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreign('primary_contact_id')
                  ->references('id')
                  ->on('contacts')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
