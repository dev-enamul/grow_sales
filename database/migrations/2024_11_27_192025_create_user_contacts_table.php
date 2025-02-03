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
        Schema::create('user_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('name')->nullable()->comment();
            $table->string('relationship_or_role')->nullable()->comment("Customer/Father/Mother/Relative/OfficeSomeone");
            $table->string('office_phone', 20)->nullable();
            $table->string('personal_phone', 20)->nullable();
            $table->string('office_email', 45)->nullable();
            $table->string('personal_email', 45)->nullable();
            $table->string('website')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('imo', 20)->nullable();
            $table->string('facebook', 100)->nullable();
            $table->string('linkedin', 100)->nullable();

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
