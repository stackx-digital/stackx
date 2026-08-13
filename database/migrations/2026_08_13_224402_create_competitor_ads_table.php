<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Competitor ads from the Meta Ad Library (§4, P2). Deduped by ad_library_id;
 * each sync updates last_seen / is_active and recomputes days_running. days_running
 * is a computed proxy for a winning ad (long-running = likely profitable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained()->cascadeOnDelete();
            $table->string('ad_library_id');
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('snapshot_url')->nullable();
            $table->string('cta')->nullable();
            $table->json('platforms')->nullable();
            $table->date('first_seen')->nullable();
            $table->date('last_seen')->nullable();
            $table->unsignedInteger('days_running')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['competitor_id', 'ad_library_id']);
            $table->index(['competitor_id', 'days_running']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_ads');
    }
};
