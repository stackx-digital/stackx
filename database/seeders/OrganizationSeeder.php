<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Seeds the single STACKx organization. Schema is multi-org ready (§4) but we
 * run one org and build no org UI. Everything else scopes to this.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::firstOrCreate(
            ['slug' => 'stackx'],
            ['name' => 'STACKx'],
        );
    }
}
