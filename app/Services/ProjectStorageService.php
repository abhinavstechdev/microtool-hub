<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProjectStorageService
{
    public const ALLOWED_EXTENSIONS = [
        'html', 'htm', 'css', 'js', 'json', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'md', 'txt'
    ];

    public const MAX_FILE_SIZE = 512000;       // 500 KB
    public const MAX_PROJECT_STORAGE = 5242880; // 5 MB
    public const MAX_FILE_COUNT = 50;

    /**
     * Get root disk directory for a project
     */
    public function getProjectDirectory(Project $project): string
    {
        return storage_path('app/private/projects/' . $project->user_id . '/' . $project->id);
    }

    /**
     * Get snapshots directory for a project
     */
    public function getSnapshotsDirectory(Project $project): string
    {
        return storage_path('app/private/snapshots/' . $project->user_id . '/' . $project->id);
    }

    /**
     * Validate and resolve safe relative path inside project directory
     */
    public function resolveSafePath(Project $project, string $relativePath): string
    {
        // Normalize slashes
        $clean = str_replace('\\', '/', trim($relativePath));
        $clean = ltrim($clean, '/');

        // Check for directory traversal, null bytes, or absolute drives
        if (str_contains($clean, "\0") || str_contains($clean, '..') || preg_match('/^[a-zA-Z]:/', $clean)) {
            throw new RuntimeException("Invalid path traversal detected: '{$relativePath}'");
        }

        $baseDir = $this->getProjectDirectory($project);
        $fullPath = $baseDir . '/' . $clean;

        // Ensure normalized full path starts with base directory
        $expectedPrefix = str_replace('\\', '/', $baseDir) . '/';
        $normalizedFullPath = str_replace('\\', '/', $fullPath);

        if (!str_starts_with($normalizedFullPath, $expectedPrefix)) {
            throw new RuntimeException("Path escapes project workspace root.");
        }

        return $fullPath;
    }

    /**
     * Check extension whitelist
     */
    public function validateExtension(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException("Prohibited file extension '.{$ext}'. Allowed: " . implode(', ', self::ALLOWED_EXTENSIONS));
        }
        return $ext;
    }

    /**
     * Write file content safely (atomic write) and record in database
     */
    public function writeFile(Project $project, string $relativePath, string $content): ProjectFile
    {
        $relativePath = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        $filename = basename($relativePath);
        $extension = $this->validateExtension($filename);

        $size = strlen($content);
        if ($size > self::MAX_FILE_SIZE) {
            throw new RuntimeException("File exceeds maximum size limit of " . (self::MAX_FILE_SIZE / 1024) . " KB.");
        }

        $baseDir = $this->getProjectDirectory($project);
        if (!File::isDirectory($baseDir)) {
            File::makeDirectory($baseDir, 0755, true);
        }

        $fullPath = $this->resolveSafePath($project, $relativePath);
        $dir = dirname($fullPath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // Check project quotas
        $existing = ProjectFile::where('project_id', $project->id)
            ->where('path', $relativePath)
            ->first();

        $currentTotalSize = (int) $project->files()->sum('size_bytes');
        $oldSize = $existing ? $existing->size_bytes : 0;
        $newTotalSize = $currentTotalSize - $oldSize + $size;

        if ($newTotalSize > self::MAX_PROJECT_STORAGE) {
            throw new RuntimeException("Project exceeds total storage quota of " . (self::MAX_PROJECT_STORAGE / (1024 * 1024)) . " MB.");
        }

        if (!$existing && $project->files()->count() >= self::MAX_FILE_COUNT) {
            throw new RuntimeException("Project exceeds maximum file count limit of " . self::MAX_FILE_COUNT . " files.");
        }

        // Atomic write
        $tempPath = $fullPath . '.' . Str::random(8) . '.tmp';
        File::put($tempPath, $content);
        File::move($tempPath, $fullPath);

        // Update database record
        $mime = $this->guessMimeType($extension);
        $hash = hash('sha256', $content);

        $file = ProjectFile::updateOrCreate(
            ['project_id' => $project->id, 'path' => $relativePath],
            [
                'filename' => $filename,
                'extension' => $extension,
                'size_bytes' => $size,
                'mime_type' => $mime,
                'hash' => $hash,
                'content' => $content,
            ]
        );

        $this->syncProjectStats($project);

        return $file;
    }

    /**
     * Read file content safely
     */
    public function readFile(Project $project, string $relativePath): string
    {
        $fullPath = $this->resolveSafePath($project, $relativePath);
        if (File::exists($fullPath) && File::isFile($fullPath)) {
            return File::get($fullPath);
        }

        // Restore from persistent PostgreSQL record if disk was wiped during container deploy/restart
        $dbFile = ProjectFile::where('project_id', $project->id)
            ->where('path', $relativePath)
            ->first();

        if ($dbFile && $dbFile->content !== null) {
            $dir = dirname($fullPath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::put($fullPath, $dbFile->content);
            return $dbFile->content;
        }

        throw new RuntimeException("File not found: '{$relativePath}'");
    }

    /**
     * Delete file safely
     */
    public function deleteFile(Project $project, string $relativePath): bool
    {
        $fullPath = $this->resolveSafePath($project, $relativePath);
        if (File::exists($fullPath) && File::isFile($fullPath)) {
            File::delete($fullPath);
        }

        ProjectFile::where('project_id', $project->id)
            ->where('path', $relativePath)
            ->delete();

        $this->syncProjectStats($project);

        return true;
    }

    /**
     * Create directory inside workspace
     */
    public function createDirectory(Project $project, string $relativeDir): bool
    {
        $fullPath = $this->resolveSafePath($project, $relativeDir);
        if (!File::isDirectory($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }
        return true;
    }

    /**
     * Rename or move a file within the project
     */
    public function renameFile(Project $project, string $oldPath, string $newPath): ProjectFile
    {
        $oldFullPath = $this->resolveSafePath($project, $oldPath);
        $newFullPath = $this->resolveSafePath($project, $newPath);

        if (!File::exists($oldFullPath) || !File::isFile($oldFullPath)) {
            throw new RuntimeException("Source file '{$oldPath}' does not exist.");
        }

        $filename = basename($newPath);
        $extension = $this->validateExtension($filename);

        $targetDir = dirname($newFullPath);
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        File::move($oldFullPath, $newFullPath);

        $fileRecord = ProjectFile::where('project_id', $project->id)
            ->where('path', $oldPath)
            ->first();

        if ($fileRecord) {
            $fileRecord->update([
                'path' => $newPath,
                'filename' => $filename,
                'extension' => $extension,
            ]);
        }

        $this->syncProjectStats($project);

        return $fileRecord ?? ProjectFile::where('project_id', $project->id)->where('path', $newPath)->first();
    }

    /**
     * Create a ZIP snapshot of the project
     */
    public function createSnapshot(Project $project, string $versionTag, string $label, ?string $description = null): ProjectSnapshot
    {
        $baseDir = $this->getProjectDirectory($project);
        $snapDir = $this->getSnapshotsDirectory($project);

        if (!File::isDirectory($snapDir)) {
            File::makeDirectory($snapDir, 0755, true);
        }

        $safeTag = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $versionTag);
        $zipName = 'snapshot_' . $safeTag . '_' . time() . '.zip';
        $zipPath = $snapDir . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Failed to initialize snapshot archive.");
        }

        $files = ProjectFile::where('project_id', $project->id)->get();
        $totalBytes = 0;

        foreach ($files as $file) {
            $abs = $baseDir . '/' . $file->path;
            if (File::exists($abs) && File::isFile($abs)) {
                $zip->addFile($abs, $file->path);
                $totalBytes += $file->size_bytes;
            }
        }

        $zip->close();

        return ProjectSnapshot::create([
            'project_id' => $project->id,
            'version_tag' => $versionTag,
            'label' => $label,
            'description' => $description,
            'archive_path' => $zipPath,
            'file_count' => $files->count(),
            'total_bytes' => $totalBytes,
        ]);
    }

    /**
     * Restore project from snapshot
     */
    public function restoreSnapshot(Project $project, ProjectSnapshot $snapshot): bool
    {
        if (!File::exists($snapshot->archive_path)) {
            throw new RuntimeException("Snapshot archive file not found.");
        }

        $baseDir = $this->getProjectDirectory($project);
        // Clean current files
        File::cleanDirectory($baseDir);
        ProjectFile::where('project_id', $project->id)->delete();

        $zip = new ZipArchive();
        if ($zip->open($snapshot->archive_path) === true) {
            $zip->extractTo($baseDir);
            $zip->close();
        } else {
            throw new RuntimeException("Failed to open snapshot archive for restoration.");
        }

        // Rescan and populate files
        $this->syncFilesFromDisk($project);
        $this->syncProjectStats($project);

        return true;
    }

    /**
     * Sync database records from disk files
     */
    public function syncFilesFromDisk(Project $project): void
    {
        $baseDir = $this->getProjectDirectory($project);
        if (!File::isDirectory($baseDir)) {
            return;
        }

        $allFiles = File::allFiles($baseDir);
        foreach ($allFiles as $fileInfo) {
            $relativePath = str_replace('\\', '/', $fileInfo->getRelativePathname());
            $filename = $fileInfo->getFilename();
            $ext = strtolower($fileInfo->getExtension());

            if (in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                $content = File::get($fileInfo->getPathname());
                ProjectFile::updateOrCreate(
                    ['project_id' => $project->id, 'path' => $relativePath],
                    [
                        'filename' => $filename,
                        'extension' => $ext,
                        'size_bytes' => $fileInfo->getSize(),
                        'mime_type' => $this->guessMimeType($ext),
                        'hash' => hash('sha256', $content),
                    ]
                );
            }
        }
    }

    /**
     * Sync stats on the Project model
     */
    public function syncProjectStats(Project $project): void
    {
        $project->update([
            'file_count' => $project->files()->count(),
            'storage_bytes' => (int) $project->files()->sum('size_bytes'),
        ]);
    }

    /**
     * Guess MIME type for allowed extensions
     */
    public function guessMimeType(string $extension): string
    {
        return match ($extension) {
            'html', 'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'md', 'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}
