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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); 
            $table->string('name');
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->enum('user_type', ['employee', 'affiliate', 'customer'])->nullable();
            $table->string('profile_image')->nullable();
            $table->enum('marital_status', ['married', 'unmarried', 'divorced'])->nullable();
            $table->date('dob')->nullable(); 
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->enum('gender', ['male', 'female', 'others'])->nullable(); 
            $table->json('senior_user')->nullable(); 
            $table->json('junior_user')->nullable();

            $table->foreignId('role_id')->nullable()->constrained();  
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
