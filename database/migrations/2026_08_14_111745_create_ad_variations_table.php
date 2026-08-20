<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI-generated ad copy variations (§4, P4). Everything stored here is AI output
 * — labelled as generated in the UI, never presented as a metric or fact. The
 * source (an ad or competitor ad, or free-form) and the input brief are kept
 * for provenance. RLS deny-all lock on Postgres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('source')->nullable();      // 'ad' | 'competitor_ad' | null (free-form)
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('product');
            $table->text('prompt');                     // the input brief/context
            $table->json('output');                     // array of generated variations
            $table->string('generated_by')->nullable(); // provider:model
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ad_variations ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_variations');
    }
};
