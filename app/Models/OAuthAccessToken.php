<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthAccessToken extends Model
{
    use HasFactory;

    protected $table = 'oauth_access_tokens';

    protected $fillable = [
        'user_id',
        'client_id',
        'token_hash',
        'scopes',
        'expires_at',
        'revoked',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return !$this->revoked && $this->expires_at->isFuture();
    }
}
