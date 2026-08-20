<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vector embeddings for semantic search (§4, P3 pgvector). One row per
 * (source, source_id, kind) — 'ad' names and 'competitor_ad' bodies. The
 * embedding column is driver-aware: a real pgvector column on Postgres (with
 * the extension + RLS lock), and a JSON-text fallback on sqlite so local dev
 * and tests work without pgvector. The stored literal ("[0.1,0.2,...]") is
 * valid for both.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dims = (int) config('embedding.dims', 1536);

        Schema::create('ad_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('source');          // 'ad' | 'competitor_ad'
            $table->unsignedBigInteger('source_id');
            $table->string('kind')->default('content');
            $table->text('content');
            $table->string('model');
            $table->unsignedSmallInteger('dims');
            $table->timestamps();

            $table->unique(['source', 'source_id', 'kind']);
            $table->index('organization_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            DB::statement("ALTER TABLE ad_embeddings ADD COLUMN embedding vector({$dims})");
            DB::statement('ALTER TABLE ad_embeddings ENABLE ROW LEVEL SECURITY');
        } else {
            DB::statement('ALTER TABLE ad_embeddings ADD COLUMN embedding text');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_embeddings');
    }
};
