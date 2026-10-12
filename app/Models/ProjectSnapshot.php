<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'version_tag',
        'label',
        'description',
        'archive_path',
        'file_count',
        'total_bytes',
    ];

    protected function casts(): array
    {
        return [
            'file_count' => 'integer',
            'total_bytes' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
