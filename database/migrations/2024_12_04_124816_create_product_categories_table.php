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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // 3. Foreign Keys
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_type_id')->nullable()->constrained('category_types')->onDelete('set null');
            $table->foreignId('measurment_unit_id')->nullable()->constrained('measurment_units')->onDelete('set null');
            $table->foreignId('area_id')->nullable()->constrained('areas')->onDelete('set null');

            // 4. Main Data Fields
            $table->string('name'); 
            $table->string('slug');
            $table->text('description')->nullable(); 
            $table->enum('progress_stage', ['Ready', 'Ongoing', 'Upcomming', 'Complete']);
            $table->date('ready_date')->nullable();
            $table->text('address')->nullable();  

            // 5. Status and Tracking
            $table->integer('status')->default(1)->comment("1=Active, 0=UnActive");
            $table->string('applies_to');

            // 6. Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();

            // 7. Unique Constraints
            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
