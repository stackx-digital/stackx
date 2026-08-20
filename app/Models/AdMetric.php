<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdMetric extends Model
{
    protected $fillable = [
        'ad_id',
        'date',
        'spend',
        'impressions',
        'reach',
        'ctr_all',
        'ctr_link',
        'cpc',
        'cpm',
        'thruplays',
        'video_3s',
        'results',
        'cost_per_result',
        'roas',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'spend' => 'decimal:2',
            'impressions' => 'integer',
            'reach' => 'integer',
            'ctr_all' => 'decimal:4',
            'ctr_link' => 'decimal:4',
            'cpc' => 'decimal:4',
            'cpm' => 'decimal:4',
            'thruplays' => 'integer',
            'video_3s' => 'integer',
            'results' => 'decimal:2',
            'cost_per_result' => 'decimal:4',
            'roas' => 'decimal:4',
        ];
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
