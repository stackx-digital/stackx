<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Report extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'title',
        'token',
        'payload',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public static function newToken(): string
    {
        return Str::random(40);
    }

    /** Public lookup by token — bypasses org scope (the token is the secret). */
    public static function findByToken(string $token): ?self
    {
        return static::withoutGlobalScope('organization')->where('token', $token)->first();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
