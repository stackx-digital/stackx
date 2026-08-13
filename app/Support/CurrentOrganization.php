<?php

namespace App\Support;

use App\Models\Organization;

/**
 * Resolves the "current" organization for app-layer scoping. STACKx runs a
 * single org today, so this returns the sole org. The schema is multi-org
 * ready (§4) — when we add org membership, this is the one place that changes
 * (e.g. derive the org from the authenticated user).
 *
 * Bound as a singleton so the id is resolved once per request.
 */
class CurrentOrganization
{
    private ?int $id = null;

    private bool $resolved = false;

    /** The current org id, or null before any org exists (fresh install / seeding). */
    public function id(): ?int
    {
        if (! $this->resolved) {
            // withoutGlobalScopes so resolving the org can't recurse into the
            // org scope that depends on this value.
            $this->id = Organization::query()->orderBy('id')->value('id');
            $this->resolved = true;
        }

        return $this->id;
    }

    /** Force a specific org (used by seeders/tests). */
    public function set(?int $id): void
    {
        $this->id = $id;
        $this->resolved = true;
    }

    public function forget(): void
    {
        $this->id = null;
        $this->resolved = false;
    }
}
