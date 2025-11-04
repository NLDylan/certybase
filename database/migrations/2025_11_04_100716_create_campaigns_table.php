<?php

use App\Enums\CampaignCompletionReason;
use App\Enums\CampaignStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('design_id');
            $table->uuid('creator_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->jsonb('variable_mapping')->nullable();
            $table->enum('status', array_column(CampaignStatus::cases(), 'value'))->default(CampaignStatus::Draft->value);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('certificate_limit')->nullable();
            $table->integer('certificates_issued')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->enum('completion_reason', array_column(CampaignCompletionReason::cases(), 'value'))->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->foreign('design_id')
                ->references('id')
                ->on('designs')
                ->onDelete('restrict');

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'created_at']);
            $table->index('design_id');
            $table->index('creator_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
