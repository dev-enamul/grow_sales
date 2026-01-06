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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('leave_type_id');
            
            $table->integer('year'); // 2024, 2025
            
            $table->integer('allocated')->default(0)->comment('New quota for this year');
            $table->integer('carried_forward')->default(0)->comment('Added from last year');
            $table->integer('total_allowed')->comment('allocated + carried_forward');
            
            $table->integer('used')->default(0);
            $table->integer('encashed')->default(0)->comment('Days exchanged for money');
            $table->integer('remaining')->comment('total - used - encashed');
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'leave_type_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
