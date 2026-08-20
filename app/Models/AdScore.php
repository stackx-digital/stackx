<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdScore extends Model
{
    protected $fillable = [
        'ad_id',
        'computed_at',
        'hook',
        'watch',
        'click',
        'convert',
        'action',
        'action_reason',
        'ai_recommendation',
        'ai_recommendation_by',
    ];

    protected function casts(): array
    {
        return [
            'computed_at' => 'datetime',
            'hook' => 'integer',
            'watch' => 'integer',
            'click' => 'integer',
            'convert' => 'integer',
        ];
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
