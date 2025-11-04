<?php

use App\Enums\DesignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('creator_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->jsonb('design_data')->nullable();
            $table->jsonb('variables')->nullable();
            $table->jsonb('settings')->nullable();
            $table->enum('status', array_column(DesignStatus::cases(), 'value'))->default(DesignStatus::Draft->value);
            $table->timestamps();

            // Foreign Keys
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index('creator_id');
            $table->index('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
