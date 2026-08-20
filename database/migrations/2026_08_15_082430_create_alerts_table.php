<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deterministic performance alerts (feature 3/4): creative fatigue (frequency
 * rising + CTR declining) and scaling opportunities (strong ROAS with room to
 * spend). Computed from daily metrics — not AI. Deduped per (ad, type) while
 * unresolved. Org-scoped, RLS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');        // fatigue | scale | cut
            $table->string('severity')->default('medium'); // low | medium | high
            $table->string('title');
            $table->text('detail')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'resolved_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE alerts ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
