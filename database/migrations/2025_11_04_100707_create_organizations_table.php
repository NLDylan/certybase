<?php

use App\Enums\OrganizationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone_number')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('coc_number')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_postal_code', 20)->nullable();
            $table->string('address_country', 2)->nullable();
            $table->enum('status', array_column(OrganizationStatus::cases(), 'value'))->default(OrganizationStatus::Active->value);
            $table->jsonb('settings')->nullable();

            // Cashier columns
            $table->string('stripe_id')->nullable()->unique();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
