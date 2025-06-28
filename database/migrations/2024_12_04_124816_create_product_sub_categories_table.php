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
        Schema::create('product_sub_categories', function (Blueprint $table) {
             $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->foreignId('product_unit_id')
                  ->nullable()
                  ->constrained('product_units')
                  ->onDelete('set null');

            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('product_categories')
                  ->onDelete('set null');

            $table->string('name');
            $table->string('slug');
            $table->string('code')->nullable(); 
            $table->text('description')->nullable();

            $table->decimal('rate', 15, 2);     
            $table->integer('quantity');      
            $table->decimal('price', 15, 2); 

            $table->foreignId('vat_setting_id')
                  ->nullable()
                  ->constrained('vat_settings')
                  ->onDelete('set null');

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->foreignId('deleted_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->softDeletes();
            $table->timestamps();

            // Unique constraint for slug within the same company
            $table->unique(['company_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sub_categories');
    }
};
