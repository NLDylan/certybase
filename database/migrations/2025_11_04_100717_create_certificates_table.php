<?php

use App\Enums\CertificateStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('design_id');
            $table->uuid('campaign_id')->nullable();
            $table->uuid('issued_to_user_id')->nullable();
            $table->string('recipient_name');
            $table->string('recipient_email');
            $table->jsonb('recipient_data')->nullable();
            $table->jsonb('certificate_data')->nullable();
            $table->string('verification_token')->unique();
            $table->enum('status', array_column(CertificateStatus::cases(), 'value'))->default(CertificateStatus::Pending->value);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
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

            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->onDelete('set null');

            $table->foreign('issued_to_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'issued_at']);
            $table->index('campaign_id');
            $table->index('design_id');
            $table->index('recipient_email');
            $table->index('issued_to_user_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
