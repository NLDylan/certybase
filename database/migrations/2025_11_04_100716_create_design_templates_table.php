<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->jsonb('template_data')->nullable();
            $table->jsonb('variables')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'is_public']);
            $table->index('category');
            $table->index('is_active');
            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_templates');
    }
};
