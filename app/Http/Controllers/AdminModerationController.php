<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Services\ProjectStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminModerationController extends Controller
{
    protected ProjectStorageService $storage;

    public function __construct(ProjectStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Ensure current user is an administrator
     */
    protected function authorizeAdmin(): void
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized: Administrator privileges required.');
        }
    }

    /**
     * Admin Moderation Dashboard
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $tab = $request->query('tab', 'pending'); // pending, published, rejected, all

        $query = Project::with(['user', 'category', 'reviewer', 'files']);

        match ($tab) {
            'published' => $query->where('status', 'published'),
            'rejected' => $query->where('status', 'rejected'),
            'all' => $query,
            default => $query->where('status', 'pending_approval'),
        };

        $projects = $query->latest('updated_at')->paginate(12)->withQueryString();

        $counts = [
            'pending' => Project::where('status', 'pending_approval')->count(),
            'published' => Project::where('status', 'published')->count(),
            'rejected' => Project::where('status', 'rejected')->count(),
            'all' => Project::count(),
        ];

        return view('admin.moderation', compact('projects', 'tab', 'counts'));
    }

    /**
     * Run automated AI Security & Code Quality Audit on a tool
     */
    public function auditTool(string $uuid): JsonResponse
    {
        $this->authorizeAdmin();

        $project = Project::where('uuid', $uuid)->with('files')->firstOrFail();

        $files = [];
        foreach ($project->files as $file) {
            try {
                $files[$file->path] = $this->storage->readFile($project, $file->path);
            } catch (\Throwable $e) {
                $files[$file->path] = $file->content ?? '';
            }
        }

        $html = $files['index.html'] ?? '';
        $css = $files['style.css'] ?? '';
        $js = $files['app.js'] ?? '';

        // Comprehensive Security & Sandbox Analysis
        $findings = [];
        $riskScore = 0; // 0 to 100

        // 1. Check index.html exists
        if (empty($html)) {
            $findings[] = [
                'type' => 'error',
                'title' => 'Missing index.html',
                'detail' => 'Tool must have a valid index.html entry point to run.',
            ];
            $riskScore += 50;
        } else {
            $findings[] = [
                'type' => 'success',
                'title' => 'Entry Point Valid',
                'detail' => 'index.html is present (' . strlen($html) . ' bytes).',
            ];
        }

        // 2. Check for unauthorized outbound networking attempts (Violates connect-src 'none')
        $networkPatterns = [
            'fetch\s*\(' => 'Direct fetch API call',
            'XMLHttpRequest' => 'XHR network request',
            'navigator\.sendBeacon' => 'Telemetry beacon',
            'WebSocket\s*\(' => 'WebSocket outbound connection',
        ];

        $foundNetwork = false;
        foreach ($networkPatterns as $pattern => $label) {
            if (preg_match('/' . $pattern . '/i', $html . $js)) {
                $findings[] = [
                    'type' => 'error',
                    'title' => 'Outbound Network Call Detected (' . $label . ')',
                    'detail' => 'Tools must execute client-side only without exfiltrating user data.',
                ];
                $riskScore += 40;
                $foundNetwork = true;
            }
        }
        if (!$foundNetwork) {
            $findings[] = [
                'type' => 'success',
                'title' => 'Client-Side Isolation Verified',
                'detail' => 'Zero outbound network or telemetry requests detected.',
            ];
        }

        // 3. Check for Cookie / Storage exfiltration or sensitive access
        if (preg_match('/document\.cookie/i', $html . $js)) {
            $findings[] = [
                'type' => 'error',
                'title' => 'Cookie Access Detected',
                'detail' => 'Tool attempts to read or modify document.cookie.',
            ];
            $riskScore += 35;
        }

        // 4. Check for eval / unsafe code execution
        if (preg_match('/\beval\s*\(/i', $html . $js)) {
            $findings[] = [
                'type' => 'warning',
                'title' => 'Dynamic eval() Usage',
                'detail' => 'eval() is detected. Verify this is legitimate (e.g. math calculator) and not code obfuscation.',
            ];
            $riskScore += 15;
        }

        // 5. Check for external script injections or cryptominers
        $externalScriptPattern = '/<script[^>]+src=["\'](https?:|\/\/)/i';
        if (preg_match($externalScriptPattern, $html)) {
            $findings[] = [
                'type' => 'warning',
                'title' => 'External Script Tag',
                'detail' => 'Tool references an external CDN script. In-browser tools should bundle dependencies or use standard web APIs.',
            ];
            $riskScore += 15;
        }

        // 6. Responsive Viewport Check
        if (str_contains($html, 'name="viewport"') || str_contains($html, "name='viewport'")) {
            $findings[] = [
                'type' => 'success',
                'title' => 'Mobile Responsive Meta Tag',
                'detail' => 'Viewport configuration tag present for mobile and tablet devices.',
            ];
        } else {
            $findings[] = [
                'type' => 'warning',
                'title' => 'Missing Mobile Viewport Meta Tag',
                'detail' => 'Recommended to include <meta name="viewport" content="width=device-width, initial-scale=1">.',
            ];
            $riskScore += 10;
        }

        // Verdict Computation
        $verdict = match (true) {
            $riskScore >= 40 => 'FLAGGED',
            $riskScore > 0 => 'WARNING',
            default => 'APPROVED',
        };

        $summary = match ($verdict) {
            'APPROVED' => 'All safety and quality checks passed. Clean client-side tool ready for public catalog.',
            'WARNING' => 'Minor warnings detected. Review tool behavior manually before publishing.',
            'FLAGGED' => 'Critical security risks or network exfiltration patterns detected. Reject or ask creator to revise.',
        };

        $report = [
            'verdict' => $verdict,
            'summary' => $summary,
            'risk_score' => $riskScore,
            'audited_at' => now()->toIso8601String(),
            'findings' => $findings,
            'stats' => [
                'file_count' => count($files),
                'html_bytes' => strlen($html),
                'css_bytes' => strlen($css),
                'js_bytes' => strlen($js),
            ],
        ];

        // Save report on project record
        $project->update([
            'ai_review_verdict' => $verdict,
            'ai_review_report' => json_encode($report),
        ]);

        AuditLog::record(Auth::id(), $project->id, 'admin:ai_audit', ['verdict' => $verdict, 'risk_score' => $riskScore]);

        return response()->json([
            'status' => 'success',
            'report' => $report,
        ]);
    }

    /**
     * Admin Approve Tool for Public Catalog
     */
    public function approve(Request $request, string $uuid): RedirectResponse
    {
        $this->authorizeAdmin();

        $project = Project::where('uuid', $uuid)->firstOrFail();

        if (!$project->files()->where('path', 'index.html')->exists()) {
            return back()->with('error', "Cannot approve: 'index.html' is missing.");
        }

        // Create version snapshot if needed
        $tag = $project->current_version ?: 'v1.0.0';
        if (!$project->snapshots()->where('version_tag', $tag)->exists()) {
            try {
                $this->storage->createSnapshot($project, $tag, 'Approved & Published');
            } catch (\Throwable $e) {
                // Ignore snapshot failure if storage directory not initialized
            }
        }

        $project->update([
            'status' => 'published',
            'visibility' => 'public',
            'rejection_reason' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'published_at' => now(),
        ]);

        AuditLog::record(Auth::id(), $project->id, 'admin:approve', [
            'name' => $project->name,
            'slug' => $project->slug,
        ]);

        return back()->with('success', "Tool '{$project->name}' has been approved and is now live in the public catalog!");
    }

    /**
     * Admin Reject Tool with Feedback
     */
    public function reject(Request $request, string $uuid): RedirectResponse
    {
        $this->authorizeAdmin();

        $project = Project::where('uuid', $uuid)->firstOrFail();
        $reason = trim($request->input('reason', 'Tool does not meet platform quality or security standards.'));

        $project->update([
            'status' => 'rejected',
            'visibility' => 'private',
            'rejection_reason' => $reason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::record(Auth::id(), $project->id, 'admin:reject', [
            'name' => $project->name,
            'reason' => $reason,
        ]);

        return back()->with('success', "Tool '{$project->name}' was rejected. Feedback was recorded for the creator.");
    }
}
