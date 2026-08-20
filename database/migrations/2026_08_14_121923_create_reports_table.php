<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shareable report snapshots (§4, P5). A report freezes the current analytics
 * (summary + winners/losers) as a JSON payload and gets a random token for a
 * read-only public link (/r/{token}) that works without login — deleting the
 * report revokes the link. RLS deny-all on Postgres; the app reads by token via
 * the owner connection, so public sharing still works.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('token', 40)->unique();
            $table->json('payload');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE reports ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
