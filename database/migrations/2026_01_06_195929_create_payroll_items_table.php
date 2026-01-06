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
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payroll_id')->constrained('payrolls')->onDelete('cascade');
            
            $table->foreignId('salary_component_id')->nullable()->constrained('salary_components')->onDelete('set null');
            $table->string('title')->comment('Snapshot of component name or manual item title');
            
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['earning', 'deduction']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
