<?php

use App\Enums\OrganizationUserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->enum('status', array_column(OrganizationUserStatus::cases(), 'value'))->default(OrganizationUserStatus::Pending->value);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('invitation_token')->nullable()->unique();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->string('invited_role')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};
