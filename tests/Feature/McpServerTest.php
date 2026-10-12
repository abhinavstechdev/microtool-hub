<?php

namespace Tests\Feature;

use App\Models\OAuthAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $rawToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::first();
        $this->rawToken = 'mcp_test_token_' . bin2hex(random_bytes(16));
        $hash = hash('sha256', $this->rawToken);

        OAuthAccessToken::create([
            'user_id' => $this->user->id,
            'client_id' => 'chatgpt_client_microtool',
            'token_hash' => $hash,
            'scopes' => 'tools:read projects:read projects:create files:write files:delete projects:publish',
            'expires_at' => now()->addDays(30),
            'revoked' => false,
        ]);
    }

    public function test_mcp_unauthenticated_returns_401_with_rfc9728_challenge(): void
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
        ]);

        $response->assertStatus(401);
        $this->assertTrue($response->headers->has('WWW-Authenticate'));
        $this->assertStringContainsString('resource_metadata=', $response->headers->get('WWW-Authenticate'));
    }

    public function test_rfc9728_resource_discovery_endpoint(): void
    {
        $response = $this->getJson('/.well-known/oauth-protected-resource');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'resource',
            'authorization_servers',
            'scopes_supported',
        ]);
    }

    public function test_rfc8414_authorization_server_metadata_endpoint(): void
    {
        $response = $this->getJson('/.well-known/oauth-authorization-server');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'response_types_supported',
            'code_challenge_methods_supported',
        ]);
    }

    public function test_openai_verification_challenge_endpoint(): void
    {
        $response = $this->get('/.well-known/openai-apps-challenge');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->getContent());
    }

    public function test_mcp_initialize_succeeds_with_bearer_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->rawToken)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.serverInfo.name', 'MicroTool Hub');
        $response->assertJsonPath('result.protocolVersion', '2024-11-05');
    }

    public function test_mcp_tools_list_contains_safety_annotations(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->rawToken)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/list',
            ]);

        $response->assertStatus(200);
        $tools = $response->json('result.tools');
        $this->assertNotEmpty($tools);

        // Verify mandatory safety annotations exist on all tools
        foreach ($tools as $tool) {
            $this->assertArrayHasKey('_meta', $tool, "Tool '{$tool['name']}' missing _meta.");
            $this->assertArrayHasKey('readOnlyHint', $tool['_meta']);
            $this->assertArrayHasKey('openWorldHint', $tool['_meta']);
            $this->assertArrayHasKey('destructiveHint', $tool['_meta']);
        }
    }

    public function test_mcp_ai_workflow_end_to_end(): void
    {
        // 1. Create project
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->rawToken)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 10,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'create_project',
                    'arguments' => [
                        'name' => 'MCP Formatter Tool',
                        'slug' => 'mcp-formatter-tool',
                        'description' => 'A clean test formatter tool.',
                    ]
                ]
            ]);
        $res1->assertStatus(200);
        $projectId = $res1->json('result.structuredContent.project.id');
        $this->assertNotNull($projectId);

        // 2. Write index.html
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->rawToken)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 11,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'write_project_file',
                    'arguments' => [
                        'project_id' => (string) $projectId,
                        'path' => 'index.html',
                        'content' => '<!DOCTYPE html><html><body><h1>Hello MCP</h1></body></html>',
                    ]
                ]
            ]);
        $res2->assertStatus(200);
        $res2->assertJsonPath('result.structuredContent.file.path', 'index.html');

        // 3. Validate project
        $res3 = $this->withHeader('Authorization', 'Bearer ' . $this->rawToken)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 12,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'validate_project',
                    'arguments' => ['project_id' => (string) $projectId]
                ]
            ]);
        $res3->assertStatus(200);
        $this->assertTrue($res3->json('result.structuredContent.valid'));

        // 4. Create snapshot
        $res4 = $this->withHeader('Authorization', 'Bearer ' . $this->rawToken)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 13,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'create_snapshot',
                    'arguments' => [
                        'project_id' => (string) $projectId,
                        'version_tag' => 'v1.0.0-draft',
                        'label' => 'Initial test draft',
                    ]
                ]
            ]);
        $res4->assertStatus(200);

        // 5. Render microtool widget
        $res5 = $this->withHeader('Authorization', 'Bearer ' . $this->rawToken)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 14,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'render_microtool_widget',
                    'arguments' => ['project_id' => (string) $projectId]
                ]
            ]);
        $res5->assertStatus(200);
        $res5->assertJsonPath('result._meta.ui.resourceUri', 'ui://microtool/preview.html');

        // 6. Publish project
        $res6 = $this->withHeader('Authorization', 'Bearer ' . $this->rawToken)
            ->postJson('/mcp', [
                'jsonrpc' => '2.0',
                'id' => 15,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'publish_project',
                    'arguments' => [
                        'project_id' => (string) $projectId,
                        'version_tag' => 'v1.0.0',
                    ]
                ]
            ]);
        $res6->assertStatus(200);
        $this->assertTrue($res6->json('result.structuredContent.published'));
    }
}
