<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competitor extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'meta_page_id',
        'search_terms',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    public function ads(): HasMany
    {
        return $this->hasMany(CompetitorAd::class);
    }
}
