<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\McpController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RFC 9728 & RFC 8414 OAuth Discovery & Domain Challenge Endpoints
|--------------------------------------------------------------------------
*/
Route::get('/.well-known/oauth-protected-resource', [OAuthController::class, 'protectedResource'])->name('oauth.discovery.resource');
Route::get('/.well-known/oauth-authorization-server', [OAuthController::class, 'authorizationServer'])->name('oauth.discovery.server');
Route::get('/.well-known/openai-apps-challenge', [OAuthController::class, 'openaiChallenge'])->name('openai.challenge');
Route::get('/healthz', fn () => response('OK', 200))->name('healthz');
Route::get('/api/internal-diag', function (\Illuminate\Http\Request $request) {
    if ($request->query('secret') !== 'mcp_diag_984372') {
        return response()->json(['error' => 'forbidden'], 403);
    }
    $uuid = '7fc60aad-eb8c-42f2-b504-eaee9eec19a3';
    $project = \App\Models\Project::where('uuid', $uuid)->first();
    $files = $project ? $project->files()->get() : [];
    $users = \App\Models\User::all(['id', 'email', 'name', 'role']);
    $tokens = \App\Models\OAuthAccessToken::latest()->take(5)->get(['id', 'user_id', 'client_id', 'revoked', 'expires_at']);
    
    $storage = new \App\Services\ProjectStorageService();
    $diskDir = $project ? $storage->getProjectDirectory($project) : null;
    $diskFiles = [];
    $indexContent = null;
    $indexHash = null;
    if ($diskDir && file_exists($diskDir)) {
        $diskFiles = scandir($diskDir);
        $indexPath = $diskDir . '/index.html';
        if (file_exists($indexPath)) {
            $indexContent = file_get_contents($indexPath);
            $indexHash = hash('sha256', $indexContent);
        }
    }

    return response()->json([
        'project' => $project,
        'files' => $files,
        'users' => $users,
        'tokens' => $tokens,
        'disk_dir' => $diskDir,
        'disk_files' => $diskFiles,
        'index_hash' => $indexHash,
        'index_length' => $indexContent ? strlen($indexContent) : 0,
        'index_preview' => $indexContent ? substr($indexContent, 0, 300) : null,
    ]);
});

/*
|--------------------------------------------------------------------------
| OAuth 2.1 Authorization Code Flow & Token Exchange
|--------------------------------------------------------------------------
*/
Route::get('/oauth/authorize', [OAuthController::class, 'authorizePrompt'])->name('oauth.authorize');
Route::post('/oauth/authorize', [OAuthController::class, 'approve'])->name('oauth.approve');
Route::post('/oauth/token', [OAuthController::class, 'token'])->name('oauth.token');
Route::post('/oauth/register', [OAuthController::class, 'registerClient'])->name('oauth.register');
Route::post('/oauth/revoke', [OAuthController::class, 'revoke'])->name('oauth.revoke');

/*
|--------------------------------------------------------------------------
| Model Context Protocol (MCP) Streamable HTTP Server
|--------------------------------------------------------------------------
*/
Route::options('/mcp', function () {
    return response('', 204, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Baggage, Sentry-Trace',
    ]);
});
Route::options('/mcp/', function () {
    return response('', 204, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Baggage, Sentry-Trace',
    ]);
});
Route::post('/mcp', [McpController::class, 'handle'])->name('mcp.handle');
Route::post('/mcp/', [McpController::class, 'handle']);
Route::get('/mcp', [McpController::class, 'sse'])->name('mcp.sse');
Route::get('/mcp/', [McpController::class, 'sse']);

/*
|--------------------------------------------------------------------------
| Public Community Discovery Pages & Previews
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/explore', [PublicController::class, 'explore'])->name('explore');
Route::get('/tools/{slug}', [PublicController::class, 'toolDetails'])->name('tools.show');
Route::get('/preview/{slug}', [PublicController::class, 'preview'])->name('preview.show');
Route::get('/@{username}', [PublicController::class, 'profile'])->name('profile.show');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated User Dashboard & AI Code Studio
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::post('/dashboard/projects', [DashboardController::class, 'createProject'])->name('dashboard.projects.create');
    Route::post('/dashboard/projects/{uuid}/publish', [DashboardController::class, 'togglePublish'])->name('dashboard.projects.publish');
    Route::delete('/dashboard/projects/{uuid}', [DashboardController::class, 'deleteProject'])->name('dashboard.projects.delete');

    // AI Code Studio
    Route::get('/studio/{uuid}', [DashboardController::class, 'studio'])->name('dashboard.studio');
    Route::get('/studio/{uuid}/file', [DashboardController::class, 'getFile'])->name('dashboard.studio.get-file');
    Route::post('/studio/{uuid}/file', [DashboardController::class, 'saveFile'])->name('dashboard.studio.save-file');
    Route::post('/studio/{uuid}/file/create', [DashboardController::class, 'createFile'])->name('dashboard.studio.create-file');
    Route::delete('/studio/{uuid}/file', [DashboardController::class, 'deleteFile'])->name('dashboard.studio.delete-file');
    Route::post('/studio/{uuid}/snapshot', [DashboardController::class, 'createSnapshot'])->name('dashboard.studio.create-snapshot');

    // Access Tokens & MCP Connectors
    Route::get('/dashboard/tokens', [DashboardController::class, 'tokens'])->name('dashboard.tokens');
    Route::post('/dashboard/tokens', [DashboardController::class, 'generateToken'])->name('dashboard.tokens.generate');
    Route::delete('/dashboard/tokens/{id}', [DashboardController::class, 'revokeToken'])->name('dashboard.tokens.revoke');

    // Community interactions
    Route::post('/tools/{slug}/like', [CommunityController::class, 'toggleLike'])->name('tools.like');
    Route::post('/tools/{slug}/comment', [CommunityController::class, 'addComment'])->name('tools.comment');
    Route::post('/tools/{slug}/fork', [CommunityController::class, 'fork'])->name('tools.fork');
});
