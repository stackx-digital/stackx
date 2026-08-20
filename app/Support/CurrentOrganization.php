<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the "current" organization for app-layer scoping (§4). In SaaS mode
 * the org is the authenticated user's tenant — this is the single boundary
 * that keeps one account's ads, reports, and settings invisible to another.
 *
 * Resolution order:
 *   1. An id explicitly set() by a seeder/test/console job.
 *   2. The authenticated user's organization_id (the normal web path).
 *   3. The first org, as a fallback for console/seeding with no auth context.
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
            $this->id = $this->resolve();
            $this->resolved = true;
        }

        return $this->id;
    }

    private function resolve(): ?int
    {
        $user = Auth::user();

        if ($user !== null && $user->organization_id !== null) {
            return (int) $user->organization_id;
        }

        // No authenticated tenant (console, seeders, public report token). Fall
        // back to the first org so local/seed workflows keep working.
        return Organization::query()->orderBy('id')->value('id');
    }

    /** Force a specific org (used by seeders/tests/registration). */
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
