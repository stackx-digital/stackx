<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'ad_id',
        'type',
        'severity',
        'title',
        'detail',
        'context',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return ['context' => 'array', 'resolved_at' => 'datetime'];
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }
}
