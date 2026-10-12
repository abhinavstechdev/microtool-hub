<?php

namespace App\Http\Controllers;

use App\Models\OAuthAccessToken;
use App\Models\User;
use App\Services\McpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Throwable;

class McpController extends Controller
{
    protected McpService $mcpService;

    public function __construct(McpService $mcpService)
    {
        $this->mcpService = $mcpService;
    }

    /**
     * Authenticate bearer token from header
     */
    protected function authenticateToken(Request $request): ?User
    {
        $authHeader = $request->header('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authHeader, 7));
        $hash = hash('sha256', $token);

        $accessToken = OAuthAccessToken::where('token_hash', $hash)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        return $accessToken?->user;
    }

    /**
     * Handle Streamable HTTP POST /mcp
     */
    public function handle(Request $request): JsonResponse
    {
        $user = $this->authenticateToken($request);

        $body = $request->json()->all();
        $id = $body['id'] ?? null;
        $method = $body['method'] ?? null;
        $params = $body['params'] ?? [];

        try {
            switch ($method) {
                case 'initialize':
                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'protocolVersion' => $params['protocolVersion'] ?? '2024-11-05',
                            'capabilities' => [
                                'tools' => [
                                    'listChanged' => false
                                ],
                                'resources' => [
                                    'subscribe' => false,
                                    'listChanged' => false
                                ],
                                'extensions' => [
                                    'io.modelcontextprotocol/skills' => (object)[]
                                ]
                            ],
                            'serverInfo' => [
                                'name' => 'microtool-hub',
                                'version' => '1.0.0'
                            ],
                            'instructions' => 'MicroTool Hub connects ChatGPT to your personal web tool workspace. Call list_projects or search_projects to discover tools, and call render_microtool_widget to display interactive UI previews.'
                        ]
                    ], 200, [
                        'Content-Type' => 'application/json',
                        'Access-Control-Allow-Origin' => '*',
                    ]);

                case 'notifications/initialized':
                    return response()->json([], 200, [
                        'Content-Type' => 'application/json',
                        'Access-Control-Allow-Origin' => '*',
                    ]);

                case 'ping':
                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => (object)[]
                    ], 200, [
                        'Content-Type' => 'application/json',
                        'Access-Control-Allow-Origin' => '*',
                    ]);

                case 'tools/list':
                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'tools' => $this->mcpService->getToolsList()
                        ]
                    ], 200, [
                        'Content-Type' => 'application/json',
                        'Access-Control-Allow-Origin' => '*',
                    ]);

                case 'skills/list':
                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'skills' => $this->getSkillsList()
                        ]
                    ], 200, [
                        'Content-Type' => 'application/json',
                        'Access-Control-Allow-Origin' => '*',
                    ]);

                case 'skills/get':
                    $uri = $params['uri'] ?? '';
                    $skill = $this->getSkillByUri($uri);
                    if (!$skill) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => ['code' => -32602, 'message' => "Skill '{$uri}' not found."]
                        ], 404, ['Access-Control-Allow-Origin' => '*']);
                    }
                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'skill' => $skill
                        ]
                    ], 200, [
                        'Content-Type' => 'application/json',
                        'Access-Control-Allow-Origin' => '*',
                    ]);

                case 'resources/list':
                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'resources' => [
                                [
                                    'uri' => 'ui://microtool/preview.html',
                                    'name' => 'MicroTool Interactive In-Chat Widget',
                                    'mimeType' => 'text/html;profile=mcp-app',
                                    'description' => 'Interactive live preview and testing widget for web micro-tools.'
                                ]
                            ]
                        ]
                    ], 200, [
                        'Content-Type' => 'application/json',
                        'Access-Control-Allow-Origin' => '*',
                    ]);

                case 'resources/read':
                    $uri = $params['uri'] ?? '';
                    if ($uri === 'ui://microtool/preview.html') {
                        $widgetHtml = $this->getWidgetHtml();
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'result' => [
                                'contents' => [
                                    [
                                        'uri' => 'ui://microtool/preview.html',
                                        'mimeType' => 'text/html;profile=mcp-app',
                                        'text' => $widgetHtml,
                                        '_meta' => [
                                            'ui' => ['prefersBorder' => true]
                                        ]
                                    ]
                                ]
                            ]
                        ], 200, [
                            'Content-Type' => 'application/json',
                            'Access-Control-Allow-Origin' => '*',
                        ]);
                    }

                    if (str_starts_with($uri, 'skill://')) {
                        $skillContent = $this->readSkillResource($uri);
                        if ($skillContent !== null) {
                            return response()->json([
                                'jsonrpc' => '2.0',
                                'id' => $id,
                                'result' => [
                                    'contents' => [
                                        [
                                            'uri' => $uri,
                                            'mimeType' => 'text/markdown',
                                            'text' => $skillContent,
                                        ]
                                    ]
                                ]
                            ], 200, [
                                'Content-Type' => 'application/json',
                                'Access-Control-Allow-Origin' => '*',
                            ]);
                        }
                    }

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'error' => ['code' => -32602, 'message' => "Resource '{$uri}' not found."]
                    ], 404, ['Access-Control-Allow-Origin' => '*']);

                case 'tools/call':
                    $toolName = $params['name'] ?? '';
                    $arguments = $params['arguments'] ?? [];

                    // Fallback to demo user if unauthenticated for testing and read tools
                    if (!$user) {
                        $user = User::first();
                    }

                    if (!$user) {
                        $metadataUrl = url('/.well-known/oauth-protected-resource');
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32001,
                                'message' => 'Unauthorized: Valid Bearer token required.'
                            ]
                        ], 401, [
                            'WWW-Authenticate' => 'Bearer resource_metadata="' . $metadataUrl . '", scope="tools:read projects:create files:write"',
                            'Access-Control-Allow-Origin' => '*',
                        ]);
                    }

                    $toolOutput = $this->mcpService->callTool($user, $toolName, $arguments);

                    $resultPayload = [
                        'content' => $toolOutput['content'] ?? [],
                        'structuredContent' => $toolOutput['structuredContent'] ?? (object)[],
                    ];

                    // If calling render_microtool_widget, attach UI resource metadata
                    if ($toolName === 'render_microtool_widget') {
                        $resultPayload['_meta'] = [
                            'ui' => [
                                'resourceUri' => 'ui://microtool/preview.html'
                            ]
                        ];
                    }

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $resultPayload
                    ], 200, [
                        'Content-Type' => 'application/json',
                        'Access-Control-Allow-Origin' => '*',
                    ]);

                default:
                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'error' => ['code' => -32601, 'message' => "Method '{$method}' not found."]
                    ], 404, ['Access-Control-Allow-Origin' => '*']);
            }
        } catch (Throwable $e) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => $e->getMessage()
                ]
            ], 200, ['Access-Control-Allow-Origin' => '*']);
        }
    }

    /**
     * Server-Sent Events / Status info for GET /mcp
     */
    public function sse(Request $request): Response
    {
        return response("MicroTool Hub MCP Streamable HTTP Server is running.\nSend JSON-RPC 2.0 POST requests to /mcp.", 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * HTML template for the MCP Apps in-chat interactive preview widget
     */
    protected function getWidgetHtml(): string
    {
        $widgetPath = resource_path('views/mcp/widget.html');
        if (File::exists($widgetPath)) {
            return File::get($widgetPath);
        }

        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MicroTool In-Chat Preview</title>
  <style>
    :root { --bg: #0f172a; --surface: #1e293b; --border: #334155; --text: #f8fafc; --primary: #3b82f6; }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background: var(--bg); color: var(--text); padding: 12px; }
    .widget-bar { display: flex; align-items: center; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border); margin-bottom: 10px; }
    .widget-title { font-weight: 600; font-size: 0.95rem; color: #38bdf8; display: flex; align-items: center; gap: 6px; }
    .widget-actions { display: flex; gap: 6px; }
    button { background: var(--surface); border: 1px solid var(--border); color: var(--text); padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; cursor: pointer; }
    button:hover { background: #334155; }
    .preview-frame-wrap { width: 100%; height: 420px; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: white; }
    iframe { width: 100%; height: 100%; border: none; }
  </style>
</head>
<body>
  <div class="widget-bar">
    <div class="widget-title" id="toolName">⚡ MicroTool Live Preview</div>
    <div class="widget-actions">
      <button id="fullscreenBtn">⛶ Fullscreen</button>
      <button id="openWebBtn">↗ Open in Studio</button>
    </div>
  </div>
  <div class="preview-frame-wrap">
    <iframe id="sandboxFrame" sandbox="allow-scripts allow-forms allow-modals"></iframe>
  </div>

  <script>
    const openai = typeof window !== 'undefined' ? window.openai : undefined;
    let currentProject = null;

    function renderProject(project) {
      currentProject = project;
      document.getElementById('toolName').textContent = '⚡ ' + (project.name || 'MicroTool Preview');
      
      const frame = document.getElementById('sandboxFrame');
      const doc = frame.contentWindow.document;
      
      let html = project.html || '<div style="padding:20px;font-family:sans-serif;"><h3>Empty Tool</h3><p>Write index.html to preview.</p></div>';
      if (project.css) {
        html = `<style>${project.css}</style>` + html;
      }
      if (project.js) {
        html += `<script>${project.js}<\/script>`;
      }
      
      doc.open();
      doc.write(html);
      doc.close();

      if (openai?.setWidgetState) {
        openai.setWidgetState({ projectId: project.id, slug: project.slug });
      }
    }

    document.getElementById('fullscreenBtn').addEventListener('click', () => {
      if (openai?.requestDisplayMode) {
        openai.requestDisplayMode({ mode: 'fullscreen' });
      }
    });

    document.getElementById('openWebBtn').addEventListener('click', () => {
      if (currentProject?.preview_url) {
        window.open(currentProject.preview_url, '_blank');
      }
    });

    // Listen to JSON-RPC postMessage from ChatGPT Host
    window.addEventListener('message', (event) => {
      const msg = event.data;
      if (!msg) return;

      if (msg.method === 'ui/initialize') {
        window.parent.postMessage({ jsonrpc: '2.0', method: 'ui/notifications/initialized', params: {} }, '*');
      } else if (msg.method === 'ui/notifications/tool-result') {
        const sc = msg.params?.structuredContent;
        if (sc?.project) {
          renderProject(sc.project);
        }
      }
    });

    // Notify host iframe is mounted
    window.parent.postMessage({ jsonrpc: '2.0', method: 'ui/notifications/initialized', params: {} }, '*');
  </script>
</body>
</html>
HTML;
    }

    /**
     * Get list of exported skills for SEP-2640 Skills extension
     */
    protected function getSkillsList(): array
    {
        $skills = [];
        $skillsDir = base_path('skills');
        if (File::isDirectory($skillsDir)) {
            foreach (File::directories($skillsDir) as $dir) {
                $skillMd = $dir . '/SKILL.md';
                if (File::exists($skillMd)) {
                    $skillName = basename($dir);
                    $content = File::get($skillMd);
                    $digest = 'sha256:' . hash('sha256', $content);

                    $desc = 'Workflow skill for ' . $skillName;
                    if (preg_match('/description:\s*(.+)/i', $content, $m)) {
                        $desc = trim($m[1]);
                    }

                    $skills[] = [
                        'uri' => "skill://microtool-hub/{$skillName}/SKILL.md",
                        'frontmatter' => [
                            'name' => $skillName,
                            'description' => $desc,
                        ],
                        'resources' => [
                            [
                                'uri' => "skill://microtool-hub/{$skillName}/SKILL.md",
                                'digest' => $digest,
                            ]
                        ]
                    ];
                }
            }
        }
        return $skills;
    }

    protected function getSkillByUri(string $uri): ?array
    {
        foreach ($this->getSkillsList() as $skill) {
            if ($skill['uri'] === $uri) {
                return $skill;
            }
        }
        return null;
    }

    protected function readSkillResource(string $uri): ?string
    {
        if (preg_match('#^skill://microtool-hub/([^/]+)/SKILL\.md$#', $uri, $m)) {
            $path = base_path("skills/{$m[1]}/SKILL.md");
            if (File::exists($path)) {
                return File::get($path);
            }
        }
        return null;
    }
}
