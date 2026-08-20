<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI-generated creative briefs (feature 2/4). Turns a winning ad, a competitor
 * ad, or a free-form product into a structured designer/copywriter brief. All
 * output is AI-generated and labelled as such. Org-scoped, RLS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creative_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('source')->nullable();       // ad | competitor_ad | null
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('product');
            $table->json('output');                     // structured brief
            $table->string('generated_by')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE creative_briefs ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('creative_briefs');
    }
};
