<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthClient extends Model
{
    use HasFactory;

    protected $table = 'oauth_clients';

    protected $fillable = [
        'user_id',
        'client_id',
        'client_secret',
        'name',
        'redirect_uris',
        'is_confidential',
        'revoked',
    ];

    protected function casts(): array
    {
        return [
            'is_confidential' => 'boolean',
            'revoked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
