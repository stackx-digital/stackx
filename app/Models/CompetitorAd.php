<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorAd extends Model
{
    protected $fillable = [
        'competitor_id',
        'ad_library_id',
        'body',
        'media_url',
        'snapshot_url',
        'cta',
        'platforms',
        'first_seen',
        'last_seen',
        'days_running',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'first_seen' => 'date',
            'last_seen' => 'date',
            'days_running' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }
}
