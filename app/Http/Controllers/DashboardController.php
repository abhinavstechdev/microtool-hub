<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\OAuthAccessToken;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ToolCategory;
use App\Services\ProjectStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    protected ProjectStorageService $storage;

    public function __construct(ProjectStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * User Projects Dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $projects = $user->projects()->with('category')->latest('updated_at')->paginate(12);
        $categories = ToolCategory::all();

        $stats = [
            'total_projects' => $user->projects()->count(),
            'published_count' => $user->projects()->where('status', 'published')->count(),
            'total_storage' => (int) $user->projects()->sum('storage_bytes'),
            'quota_bytes' => $user->storage_quota_bytes,
        ];

        return view('dashboard.index', compact('projects', 'categories', 'stats'));
    }

    /**
     * Create new project from dashboard
     */
    public function createProject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:tool_categories,id',
            'visibility' => 'required|in:private,unlisted,public',
        ]);

        $user = Auth::user();
        $slug = Str::slug($request->name);
        if (Project::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(4);
        }

        $project = Project::create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'visibility' => $request->visibility,
            'status' => 'draft',
            'current_version' => 'v1.0.0',
        ]);

        // Seed with starter index.html, style.css, and app.js
        $starterHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$project->name}</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="tool-wrapper">
    <h1>{$project->name}</h1>
    <p>Welcome to your new micro-tool! Edit this code in Studio or let ChatGPT code it via MCP.</p>
    <button id="actionBtn" class="btn">Click Me</button>
    <div id="output"></div>
  </div>
  <script src="app.js"></script>
</body>
</html>
HTML;

        $starterCss = <<<CSS
body {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  background: #0f172a;
  color: #f8fafc;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
  padding: 20px;
}
.tool-wrapper {
  background: #1e293b;
  padding: 30px;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  text-align: center;
  box-shadow: 0 10px 25px rgba(0,0,0,0.4);
}
h1 { color: #38bdf8; margin-top: 0; }
p { color: #94a3b8; line-height: 1.5; margin-bottom: 20px; }
.btn {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.15s;
}
.btn:hover { background: #2563eb; }
#output { margin-top: 20px; font-weight: 500; color: #4ade80; }
CSS;

        $starterJs = <<<JS
document.getElementById('actionBtn').addEventListener('click', () => {
  const output = document.getElementById('output');
  output.textContent = 'Button clicked at ' + new Date().toLocaleTimeString();
});
JS;

        $this->storage->writeFile($project, 'index.html', $starterHtml);
        $this->storage->writeFile($project, 'style.css', $starterCss);
        $this->storage->writeFile($project, 'app.js', $starterJs);

        AuditLog::record($user->id, $project->id, 'project:create', ['slug' => $slug]);

        return redirect()->route('dashboard.studio', ['uuid' => $project->uuid])
            ->with('success', 'Project created successfully! Welcome to AI Code Studio.');
    }

    /**
     * AI Code Studio
     */
    public function studio(string $uuid)
    {
        $user = Auth::user();
        $project = Project::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->with(['files', 'snapshots'])
            ->firstOrFail();

        $categories = ToolCategory::all();
        $files = $project->files()->orderBy('path')->get();

        // Default to index.html if present, otherwise first file
        $activeFile = $files->firstWhere('path', 'index.html') ?? $files->first();
        $activeContent = $activeFile ? $this->storage->readFile($project, $activeFile->path) : '';

        $auditLogs = AuditLog::where('project_id', $project->id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('dashboard.studio', compact('project', 'categories', 'files', 'activeFile', 'activeContent', 'auditLogs'));
    }

    /**
     * AJAX Get File Content
     */
    public function getFile(Request $request, string $uuid): JsonResponse
    {
        $user = Auth::user();
        $project = Project::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();
        $path = $request->query('path');

        $content = $this->storage->readFile($project, $path);
        return response()->json(['path' => $path, 'content' => $content]);
    }

    /**
     * AJAX Save File Content
     */
    public function saveFile(Request $request, string $uuid): JsonResponse
    {
        $user = Auth::user();
        $project = Project::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        $path = $request->input('path');
        $content = $request->input('content', '');

        $file = $this->storage->writeFile($project, $path, $content);
        AuditLog::record($user->id, $project->id, 'studio:file:save', ['path' => $path]);

        return response()->json([
            'status' => 'saved',
            'file' => [
                'path' => $file->path,
                'size_bytes' => $file->size_bytes,
                'updated_at' => $file->updated_at->toIso8601String(),
            ],
            'project' => [
                'storage_bytes' => $project->storage_bytes,
                'file_count' => $project->file_count,
            ]
        ]);
    }

    /**
     * AJAX Create File
     */
    public function createFile(Request $request, string $uuid): JsonResponse
    {
        $user = Auth::user();
        $project = Project::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        $path = $request->input('path');
        $content = $request->input('content', '');

        $file = $this->storage->writeFile($project, $path, $content);
        return response()->json(['status' => 'created', 'file' => $file]);
    }

    /**
     * AJAX Delete File
     */
    public function deleteFile(Request $request, string $uuid): JsonResponse
    {
        $user = Auth::user();
        $project = Project::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        $path = $request->input('path');
        $this->storage->deleteFile($project, $path);
        return response()->json(['status' => 'deleted', 'path' => $path]);
    }

    /**
     * AJAX Create Snapshot
     */
    public function createSnapshot(Request $request, string $uuid): JsonResponse
    {
        $user = Auth::user();
        $project = Project::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        $tag = $request->input('version_tag', 'v' . date('Ymd.His'));
        $label = $request->input('label', 'Manual snapshot');

        $snap = $this->storage->createSnapshot($project, $tag, $label);
        return response()->json(['status' => 'created', 'snapshot' => $snap]);
    }

    /**
     * Toggle Publishing
     */
    public function togglePublish(string $uuid)
    {
        $user = Auth::user();
        $project = Project::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        if ($project->status === 'published') {
            $project->update(['status' => 'draft', 'visibility' => 'private']);
            $msg = 'Tool withdrawn from public catalog.';
        } else {
            if (!$project->files()->where('path', 'index.html')->exists()) {
                return back()->with('error', 'Cannot publish: index.html is required.');
            }
            $project->update([
                'status' => 'published',
                'visibility' => 'public',
                'published_at' => now(),
            ]);
            $msg = 'Tool successfully published to community catalog!';
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete Project
     */
    public function deleteProject(string $uuid)
    {
        $user = Auth::user();
        $project = Project::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();
        $name = $project->name;
        $project->delete();

        return redirect()->route('dashboard.index')->with('success', "Project '{$name}' deleted.");
    }

    /**
     * Access Tokens & Connected Clients
     */
    public function tokens()
    {
        $user = Auth::user();
        $tokens = $user->oauthAccessTokens()->latest()->get();

        return view('dashboard.tokens', compact('tokens'));
    }

    /**
     * Generate personal token for MCP
     */
    public function generateToken(Request $request)
    {
        $user = Auth::user();
        $rawToken = 'mcp_' . Str::random(60);
        $tokenHash = hash('sha256', $rawToken);

        OAuthAccessToken::create([
            'user_id' => $user->id,
            'client_id' => 'personal_token',
            'token_hash' => $tokenHash,
            'scopes' => 'tools:read projects:read projects:create files:write files:delete projects:publish',
            'expires_at' => now()->addDays(90),
            'revoked' => false,
        ]);

        return back()->with('new_token', $rawToken);
    }

    /**
     * Revoke access token
     */
    public function revokeToken(int $id)
    {
        $user = Auth::user();
        OAuthAccessToken::where('user_id', $user->id)->where('id', $id)->update(['revoked' => true]);
        return back()->with('success', 'Token revoked successfully.');
    }
}
