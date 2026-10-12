<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthAuthCode extends Model
{
    use HasFactory;

    protected $table = 'oauth_auth_codes';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'client_id',
        'code',
        'scopes',
        'code_challenge',
        'code_challenge_method',
        'redirect_uri',
        'expires_at',
        'revoked',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'revoked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
