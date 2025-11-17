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
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->string('name');

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('areas')
                ->onDelete('set null');

            $table->foreignId('area_structure_id')
                ->constrained('area_structures')
                ->onDelete('cascade');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->float('radius_km')->nullable();
            $table->json('polygon_coordinates')->nullable();  
            $table->string('google_place_id')->nullable();

            $table->tinyInteger('status')->default(1)->comment("0 = Inactive, 1 = Active");

            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

             $table->unique(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};

