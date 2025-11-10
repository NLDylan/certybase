<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('tax_id')->nullable()->after('website');
            $table->string('coc_number')->nullable()->after('tax_id');
            $table->text('postal_address')->nullable()->after('coc_number');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['postal_address', 'coc_number', 'tax_id']);
        });
    }
};
