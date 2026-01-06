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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            
            $table->dateTime('clock_in');
            $table->dateTime('clock_out')->nullable();
            
            $table->string('reason')->nullable()->comment('lunch, break, meeting, etc');
            $table->string('ip_address')->nullable();
            $table->string('device_info')->nullable();
            $table->text('location')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
