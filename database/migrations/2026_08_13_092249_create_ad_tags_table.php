<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI-inferred creative tags (§3), populated in M5. Always labelled as inferred
 * in the UI. inferred_by records the provider/model; confidence is 0–1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();

            $table->string('format')->nullable();
            $table->string('hook_type')->nullable();
            $table->string('angle')->nullable();
            $table->string('audience')->nullable();

            $table->string('inferred_by')->nullable();     // e.g. "anthropic:claude-sonnet-5"
            $table->decimal('confidence', 4, 3)->nullable();

            $table->timestamps();

            $table->unique('ad_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_tags');
    }
};
