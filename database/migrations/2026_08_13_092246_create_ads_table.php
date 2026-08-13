<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ads (§4). meta_ad_id is nullable because CSV imports may not include the ad
 * ID column; in that case we identify an ad by (ad_account, name). thumbnail_url
 * is populated in Phase 2 when we pull creatives via the Meta API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('meta_ad_id')->nullable();
            $table->string('name');
            $table->string('status')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->timestamps();

            // Idempotent identity: prefer meta_ad_id, fall back to name.
            $table->unique(['ad_account_id', 'meta_ad_id']);
            $table->unique(['ad_account_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
