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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->unsignedBigInteger('primary_contact_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('organization_type')->nullable()->comment('Company/Institution/Government/NGO/Other');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->date('founded_date')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_active')->default(true);
            
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
        Schema::dropIfExists('organizations');
    }
};
