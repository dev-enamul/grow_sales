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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('(UUID())'));
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('campaign_type')->nullable();
            $table->string('channel')->nullable();
            $table->foreignId('area_id')->nullable()->constrained('areas')->onDelete('set null');
            $table->integer('clicks')->nullable()->default(0);
            $table->integer('impressions')->nullable()->default(0);
            $table->integer('target_leads')->nullable();
            $table->integer('target_sales')->nullable();
            $table->decimal('target_revenue', 15, 2)->nullable();
            
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
        Schema::dropIfExists('campaigns');
    }
};

