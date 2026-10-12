<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectSnapshot;
use App\Models\ToolCategory;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class McpService
{
    protected ProjectStorageService $storage;

    public function __construct(ProjectStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Get all tool definitions with strict schemas and OpenAI safety annotations
     */
    public function getToolsList(): array
    {
        return [
            [
                'name' => 'create_project',
                'description' => 'Create a new micro web tool workspace in the user account.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'Display name for the micro-tool.'],
                        'slug' => ['type' => 'string', 'description' => 'Unique URL-friendly slug.'],
                        'description' => ['type' => 'string', 'description' => 'Short summary of tool capabilities.'],
                        'category_slug' => ['type' => 'string', 'description' => 'Category slug (e.g. developer-tools, converters).'],
                        'visibility' => ['type' => 'string', 'enum' => ['private', 'unlisted', 'public'], 'default' => 'private'],
                    ],
                    'required' => ['name']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Creating project…',
                    'openai/toolInvocation/invoked' => 'Project created.',
                ]
            ],
            [
                'name' => 'list_projects',
                'description' => 'List all micro-tool workspaces owned by the authenticated user.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'default' => 20, 'description' => 'Max number of projects to return.'],
                        'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'archived'], 'description' => 'Filter by status.']
                    ]
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Fetching projects…',
                    'openai/toolInvocation/invoked' => 'Projects retrieved.',
                ]
            ],
            [
                'name' => 'get_project',
                'description' => 'Get detailed metadata and file statistics for a specific project.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID, UUID, or slug.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Loading project details…',
                    'openai/toolInvocation/invoked' => 'Details loaded.',
                ]
            ],
            [
                'name' => 'update_project_metadata',
                'description' => 'Update the name, description, category, or visibility of a project.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'name' => ['type' => 'string', 'description' => 'New display name.'],
                        'description' => ['type' => 'string', 'description' => 'New description.'],
                        'category_slug' => ['type' => 'string', 'description' => 'New category slug.'],
                        'visibility' => ['type' => 'string', 'enum' => ['private', 'unlisted', 'public']]
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Updating metadata…',
                    'openai/toolInvocation/invoked' => 'Metadata updated.',
                ]
            ],
            [
                'name' => 'delete_project',
                'description' => 'Permanently delete a project and all associated files and snapshots.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'confirm' => ['type' => 'boolean', 'description' => 'Confirmation flag. Must be true to delete.']
                    ],
                    'required' => ['project_id', 'confirm']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => true,
                    'openai/toolInvocation/invoking' => 'Deleting project…',
                    'openai/toolInvocation/invoked' => 'Project deleted.',
                ]
            ],
            [
                'name' => 'list_project_files',
                'description' => 'List all source files and directories inside a project workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Listing project files…',
                    'openai/toolInvocation/invoked' => 'File tree loaded.',
                ]
            ],
            [
                'name' => 'read_project_file',
                'description' => 'Read the text content of a web asset (HTML, CSS, JS, JSON, SVG, MD) within a project workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID, UUID, or slug.'],
                        'path' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Relative file path inside the project workspace (e.g. index.html, style.css).']
                    ],
                    'required' => ['project_id', 'path']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Reading file…',
                    'openai/toolInvocation/invoked' => 'File content loaded.',
                ]
            ],
            [
                'name' => 'write_project_file',
                'description' => 'Save or update a web asset file (HTML, CSS, JS, JSON, SVG, MD) in the project workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID, UUID, or slug.'],
                        'path' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Relative file path inside the project workspace (e.g. index.html, style.css, app.js).'],
                        'content' => ['type' => 'string', 'maxLength' => 512000, 'description' => 'Text content of the web asset to save.']
                    ],
                    'required' => ['project_id', 'path', 'content']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Writing file…',
                    'openai/toolInvocation/invoked' => 'File saved successfully.',
                ]
            ],
            [
                'name' => 'rename_project_file',
                'description' => 'Rename or move a file inside the project workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'old_path' => ['type' => 'string', 'description' => 'Current relative path.'],
                        'new_path' => ['type' => 'string', 'description' => 'New relative path.']
                    ],
                    'required' => ['project_id', 'old_path', 'new_path']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Renaming file…',
                    'openai/toolInvocation/invoked' => 'File renamed.',
                ]
            ],
            [
                'name' => 'delete_project_file',
                'description' => 'Delete a source file from the project workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'path' => ['type' => 'string', 'description' => 'Relative file path to delete.']
                    ],
                    'required' => ['project_id', 'path']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => true,
                    'openai/toolInvocation/invoking' => 'Removing file…',
                    'openai/toolInvocation/invoked' => 'File removed.',
                ]
            ],
            [
                'name' => 'create_project_directory',
                'description' => 'Create a subfolder in the project workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'path' => ['type' => 'string', 'description' => 'Relative directory path.']
                    ],
                    'required' => ['project_id', 'path']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Creating directory…',
                    'openai/toolInvocation/invoked' => 'Directory ready.',
                ]
            ],
            [
                'name' => 'validate_project',
                'description' => 'Validate project integrity, checking for index.html, tag closures, and quotas.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Validating code syntax…',
                    'openai/toolInvocation/invoked' => 'Validation complete.',
                ]
            ],
            [
                'name' => 'get_project_status',
                'description' => 'Check quota usage, file counts, and overall status of the workspace.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Checking workspace status…',
                    'openai/toolInvocation/invoked' => 'Status verified.',
                ]
            ],
            [
                'name' => 'create_snapshot',
                'description' => 'Create an immutable version snapshot backup of the current workspace state.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'version_tag' => ['type' => 'string', 'description' => 'Version identifier (e.g. v1.0.0, v1.1.0-draft).'],
                        'label' => ['type' => 'string', 'description' => 'Human readable snapshot label.'],
                        'description' => ['type' => 'string', 'description' => 'Changelog or description.']
                    ],
                    'required' => ['project_id', 'version_tag', 'label']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Creating snapshot…',
                    'openai/toolInvocation/invoked' => 'Snapshot created.',
                ]
            ],
            [
                'name' => 'list_snapshots',
                'description' => 'List all historical snapshots and version backups for a project.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Fetching version history…',
                    'openai/toolInvocation/invoked' => 'History loaded.',
                ]
            ],
            [
                'name' => 'compare_snapshots',
                'description' => 'Compare metadata and files between two snapshots.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'from_version' => ['type' => 'string', 'description' => 'Earlier version tag.'],
                        'to_version' => ['type' => 'string', 'description' => 'Later version tag.']
                    ],
                    'required' => ['project_id', 'from_version', 'to_version']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Calculating diff…',
                    'openai/toolInvocation/invoked' => 'Diff computed.',
                ]
            ],
            [
                'name' => 'restore_snapshot',
                'description' => 'Roll back the workspace files to match a previous snapshot.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'version_tag' => ['type' => 'string', 'description' => 'Version tag to restore.']
                    ],
                    'required' => ['project_id', 'version_tag']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => true,
                    'openai/toolInvocation/invoking' => 'Restoring snapshot…',
                    'openai/toolInvocation/invoked' => 'Version restored.',
                ]
            ],
            [
                'name' => 'preview_project',
                'description' => 'Generate isolated preview URL and check file completeness.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Preparing preview URL…',
                    'openai/toolInvocation/invoked' => 'Preview ready.',
                ]
            ],
            [
                'name' => 'render_microtool_widget',
                'description' => 'Render interactive in-chat MCP Apps widget for the micro-tool.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'ui' => ['resourceUri' => 'ui://microtool/preview.html'],
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Rendering in-chat widget…',
                    'openai/toolInvocation/invoked' => 'Widget displayed.',
                ]
            ],
            [
                'name' => 'publish_project',
                'description' => 'Publish an approved micro-tool to the public community catalog.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.'],
                        'version_tag' => ['type' => 'string', 'default' => 'v1.0.0', 'description' => 'Version tag to publish.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Publishing micro-tool…',
                    'openai/toolInvocation/invoked' => 'Tool published.',
                ]
            ],
            [
                'name' => 'unpublish_project',
                'description' => 'Withdraw a micro-tool from the public community directory.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Project ID or UUID.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => true,
                    'openai/toolInvocation/invoking' => 'Unpublishing tool…',
                    'openai/toolInvocation/invoked' => 'Tool unpublished.',
                ]
            ],
            [
                'name' => 'fork_project',
                'description' => 'Fork a public community micro-tool into the user account.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'string', 'description' => 'Source project ID, UUID, or slug.'],
                        'name' => ['type' => 'string', 'description' => 'Optional new name for the fork.']
                    ],
                    'required' => ['project_id']
                ],
                '_meta' => [
                    'readOnlyHint' => false,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Forking project…',
                    'openai/toolInvocation/invoked' => 'Project forked.',
                ]
            ],
            [
                'name' => 'search_tools',
                'description' => 'Search published public micro-tools in the community directory.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Search keywords.'],
                        'category' => ['type' => 'string', 'description' => 'Category slug filter.'],
                        'limit' => ['type' => 'integer', 'default' => 10]
                    ]
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Searching tools…',
                    'openai/toolInvocation/invoked' => 'Search completed.',
                ]
            ],
            [
                'name' => 'get_tool_details',
                'description' => 'Get public overview and metadata for any published community tool.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => ['type' => 'string', 'description' => 'Tool slug or UUID.']
                    ],
                    'required' => ['slug']
                ],
                '_meta' => [
                    'readOnlyHint' => true,
                    'openWorldHint' => false,
                    'destructiveHint' => false,
                    'openai/toolInvocation/invoking' => 'Loading tool overview…',
                    'openai/toolInvocation/invoked' => 'Overview retrieved.',
                ]
            ],
        ];
    }

    /**
     * Resolve project by ID, UUID, or Slug for a specific user or admin
     */
    public function resolveUserProject(User $user, string|int $identifier): Project
    {
        $identifierStr = (string) $identifier;
        $isUuid = Str::isUuid($identifierStr);
        $isNumeric = is_numeric($identifierStr);

        $query = Project::query();
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $project = $query->where(function ($q) use ($identifierStr, $isUuid, $isNumeric) {
            if ($isNumeric) {
                $q->orWhere('id', (int) $identifierStr);
            }
            if ($isUuid) {
                $q->orWhere('uuid', $identifierStr);
            }
            $q->orWhere('slug', $identifierStr);
        })->first();

        if (!$project) {
            throw new RuntimeException("Project '{$identifier}' not found or access denied.");
        }

        return $project;
    }

    /**
     * Resolve public or owned project
     */
    public function resolveAnyProject(?User $user, string|int $identifier): Project
    {
        $identifierStr = (string) $identifier;
        $isUuid = Str::isUuid($identifierStr);
        $isNumeric = is_numeric($identifierStr);

        $project = Project::where(function ($q) use ($identifierStr, $isUuid, $isNumeric) {
            if ($isNumeric) {
                $q->orWhere('id', (int) $identifierStr);
            }
            if ($isUuid) {
                $q->orWhere('uuid', $identifierStr);
            }
            $q->orWhere('slug', $identifierStr);
        })->first();

        if (!$project) {
            throw new RuntimeException("Tool '{$identifier}' not found.");
        }

        if ($project->visibility !== 'public' && (!$user || ($project->user_id !== $user->id && $user->role !== 'admin'))) {
            throw new RuntimeException("Project is private.");
        }

        return $project;
    }

    /**
     * Execute tool call
     */
    public function callTool(User $user, string $toolName, array $args): array
    {
        switch ($toolName) {
            case 'create_project':
                $name = $args['name'] ?? 'Untitled Tool';
                $slug = $args['slug'] ?? Str::slug($name);
                if (Project::where('slug', $slug)->exists()) {
                    $slug .= '-' . Str::random(4);
                }

                $categoryId = null;
                if (!empty($args['category_slug'])) {
                    $cat = ToolCategory::where('slug', $args['category_slug'])->first();
                    if ($cat) $categoryId = $cat->id;
                }

                $project = Project::create([
                    'user_id' => $user->id,
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $args['description'] ?? null,
                    'category_id' => $categoryId,
                    'visibility' => $args['visibility'] ?? 'private',
                    'status' => 'draft',
                    'current_version' => 'v1.0.0',
                ]);

                $this->storage->getProjectDirectory($project);
                AuditLog::record($user->id, $project->id, 'mcp:project:create', ['name' => $name, 'slug' => $slug]);

                return [
                    'structuredContent' => [
                        'project' => [
                            'id' => $project->id,
                            'uuid' => $project->uuid,
                            'name' => $project->name,
                            'slug' => $project->slug,
                            'visibility' => $project->visibility,
                            'studio_url' => url('/studio/' . $project->uuid),
                        ]
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Created project '{$project->name}' (Slug: {$project->slug}, ID: {$project->id}). Studio URL: " . url('/studio/' . $project->uuid)]
                    ]
                ];

            case 'list_projects':
                $query = Project::where('user_id', $user->id);
                if (!empty($args['status'])) {
                    $query->where('status', $args['status']);
                }
                $limit = min((int) ($args['limit'] ?? 20), 50);
                $projects = $query->orderByDesc('updated_at')->limit($limit)->get();

                $list = $projects->map(fn($p) => [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'status' => $p->status,
                    'visibility' => $p->visibility,
                    'file_count' => $p->file_count,
                    'storage_bytes' => $p->storage_bytes,
                    'updated_at' => $p->updated_at->toIso8601String(),
                ])->toArray();

                return [
                    'structuredContent' => ['projects' => $list],
                    'content' => [
                        ['type' => 'text', 'text' => "Found " . count($list) . " project(s)."]
                    ]
                ];

            case 'get_project':
                $project = $this->resolveUserProject($user, $args['project_id']);
                return [
                    'structuredContent' => [
                        'project' => [
                            'id' => $project->id,
                            'uuid' => $project->uuid,
                            'name' => $project->name,
                            'slug' => $project->slug,
                            'description' => $project->description,
                            'status' => $project->status,
                            'visibility' => $project->visibility,
                            'file_count' => $project->file_count,
                            'storage_bytes' => $project->storage_bytes,
                            'category' => $project->category?->name,
                            'studio_url' => url('/studio/' . $project->uuid),
                            'preview_url' => url('/preview/' . $project->slug),
                        ]
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Project: {$project->name} ({$project->status}, {$project->visibility}). Files: {$project->file_count}, Storage: {$project->storage_bytes} bytes."]
                    ]
                ];

            case 'write_project_file':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $path = $args['path'];
                $content = $args['content'];

                $file = $this->storage->writeFile($project, $path, $content);
                AuditLog::record($user->id, $project->id, 'mcp:file:write', ['path' => $path, 'size' => $file->size_bytes]);

                return [
                    'structuredContent' => [
                        'file' => [
                            'path' => $file->path,
                            'size_bytes' => $file->size_bytes,
                            'hash' => $file->hash,
                        ],
                        'project' => [
                            'id' => $project->id,
                            'file_count' => $project->file_count,
                            'storage_bytes' => $project->storage_bytes,
                        ]
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Saved '{$file->path}' ({$file->size_bytes} bytes). Total files: {$project->file_count}."]
                    ]
                ];

            case 'read_project_file':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $path = $args['path'];
                $content = $this->storage->readFile($project, $path);

                return [
                    'structuredContent' => [
                        'path' => $path,
                        'content' => $content,
                        'size_bytes' => strlen($content),
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => $content]
                    ]
                ];

            case 'list_project_files':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $files = $project->files()->get()->map(fn($f) => [
                    'path' => $f->path,
                    'size_bytes' => $f->size_bytes,
                    'extension' => $f->extension,
                    'updated_at' => $f->updated_at->toIso8601String(),
                ])->toArray();

                return [
                    'structuredContent' => ['files' => $files],
                    'content' => [
                        ['type' => 'text', 'text' => "Project has " . count($files) . " file(s)."]
                    ]
                ];

            case 'delete_project_file':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $path = $args['path'];
                $this->storage->deleteFile($project, $path);
                AuditLog::record($user->id, $project->id, 'mcp:file:delete', ['path' => $path]);

                return [
                    'structuredContent' => ['deleted' => true, 'path' => $path],
                    'content' => [
                        ['type' => 'text', 'text' => "Deleted file '{$path}'."]
                    ]
                ];

            case 'rename_project_file':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $oldPath = $args['old_path'];
                $newPath = $args['new_path'];
                $this->storage->renameFile($project, $oldPath, $newPath);

                return [
                    'structuredContent' => ['renamed' => true, 'old_path' => $oldPath, 'new_path' => $newPath],
                    'content' => [
                        ['type' => 'text', 'text' => "Renamed '{$oldPath}' to '{$newPath}'."]
                    ]
                ];

            case 'create_project_directory':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $this->storage->createDirectory($project, $args['path']);
                return [
                    'structuredContent' => ['created' => true, 'path' => $args['path']],
                    'content' => [
                        ['type' => 'text', 'text' => "Created directory '{$args['path']}'."]
                    ]
                ];

            case 'validate_project':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $hasIndex = $project->files()->where('path', 'index.html')->exists();
                $errors = [];
                $warnings = [];

                if (!$hasIndex) {
                    $errors[] = "Missing 'index.html' entrypoint.";
                }

                $files = $project->files()->get();
                foreach ($files as $f) {
                    if ($f->extension === 'html') {
                        $html = $this->storage->readFile($project, $f->path);
                        $openDivs = substr_count($html, '<div');
                        $closeDivs = substr_count($html, '</div>');
                        if ($openDivs !== $closeDivs) {
                            $warnings[] = "Unbalanced <div> tags in {$f->path} ({$openDivs} open, {$closeDivs} closed).";
                        }
                    }
                }

                $valid = count($errors) === 0;

                return [
                    'structuredContent' => [
                        'valid' => $valid,
                        'errors' => $errors,
                        'warnings' => $warnings,
                        'file_count' => $project->file_count,
                        'storage_bytes' => $project->storage_bytes,
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => $valid ? "Validation passed. Project has index.html and " . count($files) . " valid files." : "Validation failed: " . implode(', ', $errors)]
                    ]
                ];

            case 'get_project_status':
                $project = $this->resolveUserProject($user, $args['project_id']);
                return [
                    'structuredContent' => [
                        'id' => $project->id,
                        'status' => $project->status,
                        'visibility' => $project->visibility,
                        'file_count' => $project->file_count,
                        'storage_bytes' => $project->storage_bytes,
                        'quota_bytes' => ProjectStorageService::MAX_PROJECT_STORAGE,
                        'latest_snapshot' => $project->snapshots()->latest()->first()?->version_tag,
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Status: {$project->status}, Storage: {$project->storage_bytes} / " . ProjectStorageService::MAX_PROJECT_STORAGE . " bytes."]
                    ]
                ];

            case 'create_snapshot':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $tag = $args['version_tag'];
                $label = $args['label'];
                $desc = $args['description'] ?? null;

                $snap = $this->storage->createSnapshot($project, $tag, $label, $desc);
                AuditLog::record($user->id, $project->id, 'mcp:snapshot:create', ['tag' => $tag]);

                return [
                    'structuredContent' => [
                        'snapshot' => [
                            'id' => $snap->id,
                            'version_tag' => $snap->version_tag,
                            'label' => $snap->label,
                            'file_count' => $snap->file_count,
                            'total_bytes' => $snap->total_bytes,
                        ]
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Snapshot '{$tag}' created ({$snap->file_count} files, {$snap->total_bytes} bytes)."]
                    ]
                ];

            case 'list_snapshots':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $snaps = $project->snapshots()->latest()->get()->map(fn($s) => [
                    'id' => $s->id,
                    'version_tag' => $s->version_tag,
                    'label' => $s->label,
                    'file_count' => $s->file_count,
                    'created_at' => $s->created_at->toIso8601String(),
                ])->toArray();

                return [
                    'structuredContent' => ['snapshots' => $snaps],
                    'content' => [
                        ['type' => 'text', 'text' => "Found " . count($snaps) . " snapshot(s)."]
                    ]
                ];

            case 'restore_snapshot':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $tag = $args['version_tag'];
                $snap = $project->snapshots()->where('version_tag', $tag)->first();
                if (!$snap) {
                    throw new RuntimeException("Snapshot version '{$tag}' not found.");
                }

                $this->storage->restoreSnapshot($project, $snap);
                AuditLog::record($user->id, $project->id, 'mcp:snapshot:restore', ['tag' => $tag]);

                return [
                    'structuredContent' => ['restored' => true, 'version_tag' => $tag],
                    'content' => [
                        ['type' => 'text', 'text' => "Restored project to snapshot '{$tag}'."]
                    ]
                ];

            case 'preview_project':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $previewUrl = url('/preview/' . $project->slug);

                return [
                    'structuredContent' => [
                        'preview_url' => $previewUrl,
                        'slug' => $project->slug,
                        'has_index' => $project->files()->where('path', 'index.html')->exists(),
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Preview URL: {$previewUrl}"]
                    ]
                ];

            case 'render_microtool_widget':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $indexHtml = '';
                $styleCss = '';
                $appJs = '';

                if ($project->files()->where('path', 'index.html')->exists()) {
                    $indexHtml = $this->storage->readFile($project, 'index.html');
                }
                if ($project->files()->where('path', 'style.css')->exists()) {
                    $styleCss = $this->storage->readFile($project, 'style.css');
                }
                if ($project->files()->where('path', 'app.js')->exists()) {
                    $appJs = $this->storage->readFile($project, 'app.js');
                }

                return [
                    'structuredContent' => [
                        'project' => [
                            'id' => $project->id,
                            'name' => $project->name,
                            'slug' => $project->slug,
                            'preview_url' => url('/preview/' . $project->slug),
                            'html' => $indexHtml,
                            'css' => $styleCss,
                            'js' => $appJs,
                        ]
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Rendering in-chat interactive preview for '{$project->name}'."]
                    ]
                ];

            case 'publish_project':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $tag = $args['version_tag'] ?? 'v1.0.0';

                // Check index.html exists
                if (!$project->files()->where('path', 'index.html')->exists()) {
                    throw new RuntimeException("Cannot publish: 'index.html' is missing.");
                }

                // Create published snapshot if not present
                if (!$project->snapshots()->where('version_tag', $tag)->exists()) {
                    $this->storage->createSnapshot($project, $tag, 'Published ' . $tag);
                }

                $project->update([
                    'status' => 'published',
                    'visibility' => 'public',
                    'current_version' => $tag,
                    'published_at' => now(),
                ]);

                AuditLog::record($user->id, $project->id, 'project:publish', ['version' => $tag]);

                return [
                    'structuredContent' => [
                        'published' => true,
                        'tool_url' => url('/tools/' . $project->slug),
                        'preview_url' => url('/preview/' . $project->slug),
                        'version' => $tag,
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Tool '{$project->name}' published successfully! Community URL: " . url('/tools/' . $project->slug)]
                    ]
                ];

            case 'unpublish_project':
                $project = $this->resolveUserProject($user, $args['project_id']);
                $project->update([
                    'status' => 'draft',
                    'visibility' => 'private',
                ]);
                AuditLog::record($user->id, $project->id, 'project:unpublish');

                return [
                    'structuredContent' => ['unpublished' => true],
                    'content' => [
                        ['type' => 'text', 'text' => "Withdrew '{$project->name}' from public catalog. Status is now draft."]
                    ]
                ];

            case 'fork_project':
                $source = $this->resolveAnyProject($user, $args['project_id']);
                $forkName = $args['name'] ?? ($source->name . ' (Fork)');
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

                // Copy source files
                $files = $source->files()->get();
                foreach ($files as $file) {
                    $content = $this->storage->readFile($source, $file->path);
                    $this->storage->writeFile($fork, $file->path, $content);
                }

                $source->increment('forks_count');
                AuditLog::record($user->id, $fork->id, 'project:fork', ['source_id' => $source->id]);

                return [
                    'structuredContent' => [
                        'forked' => true,
                        'project' => [
                            'id' => $fork->id,
                            'uuid' => $fork->uuid,
                            'name' => $fork->name,
                            'slug' => $fork->slug,
                            'studio_url' => url('/studio/' . $fork->uuid),
                        ]
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Forked '{$source->name}' into new workspace '{$fork->name}'. Studio URL: " . url('/studio/' . $fork->uuid)]
                    ]
                ];

            case 'search_tools':
                $query = $args['query'] ?? '';
                $catSlug = $args['category'] ?? null;
                $limit = min((int) ($args['limit'] ?? 10), 30);

                $q = Project::where('status', 'published')
                    ->where('visibility', 'public');

                if ($query) {
                    $q->where(function($sub) use ($query) {
                        $sub->where('name', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                }

                if ($catSlug) {
                    $cat = ToolCategory::where('slug', $catSlug)->first();
                    if ($cat) $q->where('category_id', $cat->id);
                }

                $tools = $q->orderByDesc('likes_count')->limit($limit)->get()->map(fn($t) => [
                    'name' => $t->name,
                    'slug' => $t->slug,
                    'description' => $t->description,
                    'category' => $t->category?->name,
                    'author' => $t->user->name,
                    'likes' => $t->likes_count,
                    'url' => url('/tools/' . $t->slug),
                ])->toArray();

                return [
                    'structuredContent' => ['tools' => $tools],
                    'content' => [
                        ['type' => 'text', 'text' => "Found " . count($tools) . " tool(s)."]
                    ]
                ];

            case 'get_tool_details':
                $tool = Project::where('status', 'published')
                    ->where('visibility', 'public')
                    ->where(fn($q) => $q->where('slug', $args['slug'])->orWhere('uuid', $args['slug']))
                    ->first();

                if (!$tool) {
                    throw new RuntimeException("Published tool '{$args['slug']}' not found.");
                }

                return [
                    'structuredContent' => [
                        'tool' => [
                            'name' => $tool->name,
                            'slug' => $tool->slug,
                            'description' => $tool->description,
                            'author' => $tool->user->name,
                            'category' => $tool->category?->name,
                            'version' => $tool->current_version,
                            'likes' => $tool->likes_count,
                            'views' => $tool->views_count,
                            'url' => url('/tools/' . $tool->slug),
                            'preview_url' => url('/preview/' . $tool->slug),
                        ]
                    ],
                    'content' => [
                        ['type' => 'text', 'text' => "Tool: {$tool->name} by {$tool->user->name}. URL: " . url('/tools/' . $tool->slug)]
                    ]
                ];

            case 'delete_project':
                if (empty($args['confirm'])) {
                    throw new RuntimeException("Must pass 'confirm: true' to delete project.");
                }
                $project = $this->resolveUserProject($user, $args['project_id']);
                $name = $project->name;
                $project->delete();
                AuditLog::record($user->id, null, 'project:delete', ['name' => $name]);

                return [
                    'structuredContent' => ['deleted' => true],
                    'content' => [
                        ['type' => 'text', 'text' => "Project '{$name}' permanently deleted."]
                    ]
                ];

            default:
                throw new RuntimeException("Unknown tool '{$toolName}'.");
        }
    }
}
