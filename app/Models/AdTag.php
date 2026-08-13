<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdTag extends Model
{
    protected $fillable = [
        'ad_id',
        'format',
        'hook_type',
        'angle',
        'audience',
        'inferred_by',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:3',
        ];
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
