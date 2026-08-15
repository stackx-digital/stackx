<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Items saved to a board. A source ('ad' | 'competitor_ad') references our data,
 * or 'external' stores a pasted swipe (title/body/media). A note captures why
 * it's worth keeping. Org-scoped, RLS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('external'); // ad | competitor_ad | external
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['board_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE board_items ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('board_items');
    }
};
