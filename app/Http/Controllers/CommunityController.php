<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Project;
use App\Services\ProjectStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    protected ProjectStorageService $storage;

    public function __construct(ProjectStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Toggle like on a published project
     */
    public function toggleLike(string $slug)
    {
        $user = Auth::user();
        $project = Project::where('slug', $slug)->firstOrFail();

        $existing = Like::where('user_id', $user->id)->where('project_id', $project->id)->first();
        if ($existing) {
            $existing->delete();
            $project->decrement('likes_count');
            $liked = false;
        } else {
            Like::create(['user_id' => $user->id, 'project_id' => $project->id]);
            $project->increment('likes_count');
            $liked = true;
        }

        if (request()->wantsJson()) {
            return response()->json(['liked' => $liked, 'likes_count' => $project->likes_count]);
        }

        return back();
    }

    /**
     * Add comment to a tool
     */
    public function addComment(Request $request, string $slug)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $project = Project::where('slug', $slug)->firstOrFail();

        Comment::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'content' => $request->content,
            'status' => 'approved',
        ]);

        return back()->with('success', 'Comment posted!');
    }

    /**
     * One-click Fork Project into user account
     */
    public function fork(string $slug)
    {
        $user = Auth::user();
        $source = Project::where('slug', $slug)->where('visibility', 'public')->firstOrFail();

        $forkName = $source->name . ' (Fork)';
        $forkSlug = Str::slug($forkName) . '-' . Str::random(4);

        $fork = Project::create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'name' => $forkName,
            'slug' => $forkSlug,
            'description' => $source->description,
            'category_id' => $source->category_id,
            'visibility' => 'private',
            'status' => 'draft',
            'current_version' => 'v1.0.0',
            'fork_of_id' => $source->id,
        ]);

        // Copy files
        foreach ($source->files as $file) {
            $content = $this->storage->readFile($source, $file->path);
            $this->storage->writeFile($fork, $file->path, $content);
        }

        $source->increment('forks_count');
        AuditLog::record($user->id, $fork->id, 'project:fork', ['source_id' => $source->id]);

        return redirect()->route('dashboard.studio', ['uuid' => $fork->uuid])
            ->with('success', "Forked '{$source->name}' into your workspace! You can now customize it.");
    }
}
