<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the RLS deny-all lock to organization_settings — the table holds
 * encrypted tenant credentials, so it gets the same DB-layer denial as the
 * other org tables. Postgres-only; a no-op on sqlite.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('organization_settings')) {
            DB::statement('ALTER TABLE organization_settings ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('organization_settings')) {
            DB::statement('ALTER TABLE organization_settings DISABLE ROW LEVEL SECURITY');
        }
    }
};
