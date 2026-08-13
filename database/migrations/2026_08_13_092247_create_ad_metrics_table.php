<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily ad metrics (§4). Every metric is nullable: if a CSV lacks a column we
 * store null (never 0), so the scoring engine (M3) can honestly mark a score
 * N/A rather than fabricate one. Unique (ad_id, date) makes re-imports
 * idempotent — the same day upserts in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->decimal('spend', 14, 2)->nullable();
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->decimal('ctr_all', 8, 4)->nullable();      // percent, e.g. 1.2345
            $table->decimal('ctr_link', 8, 4)->nullable();     // percent
            $table->decimal('cpc', 12, 4)->nullable();
            $table->decimal('cpm', 12, 4)->nullable();
            $table->unsignedBigInteger('thruplays')->nullable();
            $table->unsignedBigInteger('video_3s')->nullable();
            $table->decimal('results', 14, 2)->nullable();
            $table->decimal('cost_per_result', 12, 4)->nullable();
            $table->decimal('roas', 10, 4)->nullable();

            $table->timestamps();

            $table->unique(['ad_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_metrics');
    }
};
