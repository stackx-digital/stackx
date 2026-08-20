<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Connected Meta ad accounts (§4). The API token is referenced, not stored
 * inline — the Meta API sync is behind a feature flag until app review, so
 * token_ref stays nullable and CSV import is the MVP ingest path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('meta_ad_account_id')->nullable();
            $table->string('currency', 3)->default('MYR');
            $table->string('token_ref')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'meta_ad_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
