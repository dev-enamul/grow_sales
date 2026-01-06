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
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id');
            
            $table->string('name'); // Basic, House Rent, Medical, Tax
            $table->string('slug')->nullable()->unique(); // System identifier: basic, house_rent, tax
            $table->enum('type', ['earning', 'deduction']); // Earning (+) or Deduction (-)
            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false); // If true, cannot delete/change slug
            $table->boolean('is_system_generated')->default(false); // If true, calculated by policy
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed Default System Components
        $defaults = [
            ['name' => 'Basic Salary', 'slug' => 'basic', 'type' => 'earning', 'is_system_generated' => false],
            ['name' => 'House Rent', 'slug' => 'house_rent', 'type' => 'earning', 'is_system_generated' => false],
            ['name' => 'Medical Allowance', 'slug' => 'medical', 'type' => 'earning', 'is_system_generated' => false],
            ['name' => 'Conveyance', 'slug' => 'conveyance', 'type' => 'earning', 'is_system_generated' => false],
        ];

        foreach ($defaults as $comp) {
            \Illuminate\Support\Facades\DB::table('salary_components')->insert([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'company_id' => 1, // Default Company
                'name' => $comp['name'],
                'slug' => $comp['slug'],
                'type' => $comp['type'],
                'is_locked' => true,
                'is_system_generated' => $comp['is_system_generated'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
