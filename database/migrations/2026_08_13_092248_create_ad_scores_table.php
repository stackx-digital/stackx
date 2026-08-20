<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deterministic funnel scores (§3), computed in M3. Each score is a percentile
 * 0–100 within the ad account, or null for N/A when a metric is missing
 * account-wide. action ∈ scale|keep|cut with a threshold-based reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
            $table->timestamp('computed_at');

            $table->unsignedTinyInteger('hook')->nullable();
            $table->unsignedTinyInteger('watch')->nullable();
            $table->unsignedTinyInteger('click')->nullable();
            $table->unsignedTinyInteger('convert')->nullable();

            $table->string('action')->nullable();          // scale | keep | cut
            $table->string('action_reason')->nullable();

            $table->timestamps();

            $table->unique('ad_id'); // latest scores per ad
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_scores');
    }
};
