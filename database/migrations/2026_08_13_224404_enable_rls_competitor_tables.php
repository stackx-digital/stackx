<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the RLS deny-all lock (see enable_rls_deny_all) to the P2 competitor
 * tables. Postgres-only; a no-op on sqlite. ENABLE (not FORCE) so the owner
 * role Laravel connects as keeps access while API roles are denied.
 */
return new class extends Migration
{
    private array $tables = ['competitors', 'competitor_ads'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            }
        }
    }
};
