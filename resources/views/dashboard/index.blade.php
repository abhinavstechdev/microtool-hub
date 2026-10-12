@extends('layouts.app')

@section('title', 'My Projects Dashboard — MicroTool Hub')

@section('content')
<div class="container py-4">
  <!-- Dashboard Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h1 class="fw-bold mb-1">Developer Dashboard</h1>
      <p class="text-muted small mb-0">Manage your web micro-tools, launch AI Code Studio, or connect ChatGPT via MCP.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('dashboard.tokens') }}" class="btn btn-sm btn-outline-info">
        <i class="bi bi-key me-1"></i> MCP Tokens
      </a>
      <button class="btn btn-sm btn-gradient" data-bs-toggle="modal" data-bs-target="#newProjectModal">
        <i class="bi bi-plus-lg me-1"></i> New Micro-Tool
      </button>
    </div>
  </div>

  <!-- Stats & Storage Quota -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border p-3">
        <div class="text-muted small">Total Workspaces</div>
        <div class="fs-3 fw-bold text-info">{{ $stats['total_projects'] }}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border p-3">
        <div class="text-muted small">Published Tools</div>
        <div class="fs-3 fw-bold text-success">{{ $stats['published_count'] }}</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card border p-3">
        <div class="d-flex justify-content-between text-muted small mb-1">
          <span>Storage Usage</span>
          <span class="font-mono">{{ round($stats['total_storage'] / 1024, 1) }} KB / {{ round($stats['quota_bytes'] / (1024 * 1024), 1) }} MB</span>
        </div>
        @php
          $percent = min(100, round(($stats['total_storage'] / max(1, $stats['quota_bytes'])) * 100, 1));
        @endphp
        <div class="progress" style="height: 8px;">
          <div class="progress-bar bg-info" role="progressbar" style="width: {{ $percent }}%;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Projects Grid -->
  <h4 class="fw-bold mb-3">Your Projects</h4>
  <div class="row g-4">
    @forelse($projects as $project)
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 tool-card p-3 d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge {{ $project->status === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} small text-uppercase">
              {{ $project->status }}
            </span>
            <span class="badge bg-body-tertiary border text-muted small font-mono">{{ $project->visibility }}</span>
          </div>

          <h5 class="fw-bold mb-1">
            <a href="{{ route('dashboard.studio', $project->uuid) }}" class="text-decoration-none text-body">
              {{ $project->name }}
            </a>
          </h5>

          <p class="text-muted small flex-grow-1 mb-2">
            {{ Str::limit($project->description ?? 'No description provided.', 80) }}
          </p>

          <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted font-mono mb-3">
            <span>{{ $project->file_count }} files</span>
            <span>{{ round($project->storage_bytes / 1024, 1) }} KB</span>
          </div>

          <!-- Actions -->
          <div class="d-flex gap-2">
            <a href="{{ route('dashboard.studio', $project->uuid) }}" class="btn btn-sm btn-primary flex-grow-1">
              <i class="bi bi-code-slash me-1"></i> Open Studio
            </a>
            <a href="{{ route('preview.show', $project->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview">
              <i class="bi bi-play"></i>
            </a>
            <!-- Publish Toggle -->
            <form action="{{ route('dashboard.projects.publish', $project->uuid) }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-sm {{ $project->status === 'published' ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $project->status === 'published' ? 'Unpublish' : 'Publish' }}">
                <i class="bi bi-globe"></i>
              </button>
            </form>
            <!-- Delete -->
            <form action="{{ route('dashboard.projects.delete', $project->uuid) }}" method="POST" onsubmit="return confirm('Delete this project permanently?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-center py-5">
        <div class="fs-1 text-muted mb-3"><i class="bi bi-folder-plus"></i></div>
        <h4>No projects yet</h4>
        <p class="text-muted small">Create your first micro-tool workspace or instruct ChatGPT via MCP.</p>
        <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#newProjectModal">Create Micro-Tool</button>
      </div>
    @endforelse
  </div>

  <div class="mt-4">
    {{ $projects->links('pagination::bootstrap-5') }}
  </div>
</div>

<!-- New Project Modal -->
<div class="modal fade" id="newProjectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('dashboard.projects.create') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Create New Micro-Tool</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tool Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Markdown Previewer" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Short Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Briefly describe what this micro-tool does..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Category</label>
            <select name="category_id" class="form-select">
              <option value="">Uncategorized</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Initial Visibility</label>
            <select name="visibility" class="form-select">
              <option value="private">Private (Only you & ChatGPT)</option>
              <option value="unlisted">Unlisted (Anyone with secret link)</option>
              <option value="public">Public (Discoverable in community)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gradient">Create & Open Studio</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
