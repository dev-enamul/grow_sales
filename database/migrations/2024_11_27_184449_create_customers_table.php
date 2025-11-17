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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('customer_id')->nullable()->comment("CUS-001");    
            $table->foreignId('referred_by')->nullable()->constrained('users');

            $table->integer('total_sales')->default(0)->nullable()->comment("Total Products Sales");
            $table->integer('total_sales_amount')->default(0)->nullable()->comment("Total Amount of  Sales"); 
            $table->boolean('newsletter_subscribed')->default(true);

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
        Schema::dropIfExists('customers');
    }
};
