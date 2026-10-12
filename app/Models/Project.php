<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uuid',
        'name',
        'slug',
        'description',
        'category_id',
        'visibility',
        'status',
        'current_version',
        'fork_of_id',
        'likes_count',
        'views_count',
        'forks_count',
        'storage_bytes',
        'file_count',
        'is_featured',
        'published_at',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'ai_review_verdict',
        'ai_review_report',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'likes_count' => 'integer',
            'views_count' => 'integer',
            'forks_count' => 'integer',
            'storage_bytes' => 'integer',
            'file_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ToolCategory::class, 'category_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ProjectSnapshot::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProjectTag::class, 'project_tag_map', 'project_id', 'tag_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function forkOf(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'fork_of_id');
    }

    public function forks(): HasMany
    {
        return $this->hasMany(Project::class, 'fork_of_id');
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
