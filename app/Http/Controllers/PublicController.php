<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\ProjectStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicController extends Controller
{
    protected ProjectStorageService $storage;

    public function __construct(ProjectStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Homepage
     */
    public function home()
    {
        $featuredTools = Project::where('status', 'published')
            ->where('visibility', 'public')
            ->where('is_featured', true)
            ->with(['user', 'category'])
            ->limit(6)
            ->get();

        $recentTools = Project::where('status', 'published')
            ->where('visibility', 'public')
            ->with(['user', 'category'])
            ->latest('published_at')
            ->limit(8)
            ->get();

        $categories = ToolCategory::withCount(['projects' => function ($q) {
            $q->where('status', 'published')->where('visibility', 'public');
        }])->orderBy('display_order')->get();

        $stats = [
            'total_tools' => Project::where('status', 'published')->where('visibility', 'public')->count(),
            'total_creators' => User::count(),
            'total_forks' => (int) Project::sum('forks_count'),
        ];

        return view('public.home', compact('featuredTools', 'recentTools', 'categories', 'stats'));
    }

    /**
     * Explore tools directory with search and filtering
     */
    public function explore(Request $request)
    {
        $query = $request->query('q');
        $catSlug = $request->query('category');
        $sort = $request->query('sort', 'popular'); // popular, recent, forked

        $toolsQuery = Project::where('status', 'published')
            ->where('visibility', 'public')
            ->with(['user', 'category']);

        if ($query) {
            $toolsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });
        }

        if ($catSlug) {
            $cat = ToolCategory::where('slug', $catSlug)->first();
            if ($cat) {
                $toolsQuery->where('category_id', $cat->id);
            }
        }

        match ($sort) {
            'recent' => $toolsQuery->latest('published_at'),
            'forked' => $toolsQuery->orderByDesc('forks_count'),
            default => $toolsQuery->orderByDesc('likes_count'),
        };

        $tools = $toolsQuery->paginate(12)->withQueryString();
        $categories = ToolCategory::all();

        return view('public.explore', compact('tools', 'categories', 'query', 'catSlug', 'sort'));
    }

    /**
     * Tool details page
     */
    public function toolDetails(string $slug)
    {
        $tool = Project::where('slug', $slug)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->with(['user', 'category', 'comments.user', 'files'])
            ->firstOrFail();

        $tool->increment('views_count');

        $isLiked = auth()->check() && $tool->likes()->where('user_id', auth()->id())->exists();

        $relatedTools = Project::where('status', 'published')
            ->where('visibility', 'public')
            ->where('id', '!=', $tool->id)
            ->where('category_id', $tool->category_id)
            ->limit(4)
            ->get();

        return view('public.tool-details', compact('tool', 'isLiked', 'relatedTools'));
    }

    /**
     * Public Creator Profile
     */
    public function profile(string $username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $tools = $user->projects()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->with('category')
            ->latest('published_at')
            ->get();

        return view('public.profile', compact('user', 'tools'));
    }

    /**
     * Safe isolated sandbox preview
     */
    public function preview(string $slug): Response
    {
        $project = Project::where('slug', $slug)
            ->where(function ($q) {
                if (auth()->check()) {
                    $q->where('visibility', 'public')
                      ->orWhere('visibility', 'unlisted')
                      ->orWhere('user_id', auth()->id());
                } else {
                    $q->where('visibility', 'public')
                      ->orWhere('visibility', 'unlisted');
                }
            })->firstOrFail();

        // Get index.html
        $html = '<!DOCTYPE html><html><body><h3>Workspace is empty</h3><p>Write an index.html file to preview this project.</p></body></html>';
        if ($project->files()->where('path', 'index.html')->exists()) {
            $html = $this->storage->readFile($project, 'index.html');
        }

        // If index references local CSS/JS, rewrite them to inline or base URLs
        if ($project->files()->where('path', 'style.css')->exists()) {
            $css = $this->storage->readFile($project, 'style.css');
            $html = str_replace('</head>', "<style>\n/* Injected style.css */\n{$css}\n</style>\n</head>", $html);
        }

        if ($project->files()->where('path', 'app.js')->exists()) {
            $js = $this->storage->readFile($project, 'app.js');
            $html = str_replace('</body>', "<script>\n// Injected app.js\n{$js}\n</script>\n</body>", $html);
        }

        // Restrictive CSP Header: allows client-side assets and blob URLs for in-browser file tools, with zero external network exfiltration (connect-src 'none')
        $csp = "default-src 'self' 'unsafe-inline' data: blob:; " .
               "img-src 'self' data: blob:; " .
               "media-src 'self' data: blob:; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' data: https://fonts.gstatic.com; " .
               "worker-src 'self' blob:; " .
               "child-src 'self' blob:; " .
               "connect-src 'none'; " .
               "frame-ancestors *;";

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => $csp,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
