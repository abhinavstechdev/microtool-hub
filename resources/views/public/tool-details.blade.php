@extends('layouts.app')

@section('title', $tool->name . ' — MicroTool Hub')
@section('meta_description', $tool->description)

@section('content')
<div class="container py-4">
  <!-- Tool Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-body-tertiary border text-info small">
          <i class="bi {{ $tool->category?->icon ?? 'bi-tools' }} me-1"></i> {{ $tool->category?->name ?? 'Utility' }}
        </span>
        <span class="badge bg-secondary-subtle text-secondary font-mono">{{ $tool->current_version }}</span>
        @if($tool->fork_of_id)
          <span class="badge bg-warning-subtle text-warning small"><i class="bi bi-git me-1"></i>Forked</span>
        @endif
      </div>
      <h1 class="fw-bold mb-1">{{ $tool->name }}</h1>
      <p class="text-muted small mb-0">Created by <a href="{{ route('profile.show', $tool->user->username ?? $tool->user->id) }}" class="text-decoration-none fw-semibold text-info">{{ $tool->user->name }}</a> &bull; Published {{ $tool->published_at?->diffForHumans() }}</p>
    </div>

    <!-- Actions -->
    <div class="d-flex gap-2">
      <!-- Like Form -->
      <form action="{{ route('tools.like', $tool->slug) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-sm {{ $isLiked ? 'btn-danger' : 'btn-outline-danger' }}">
          <i class="bi bi-heart{{ $isLiked ? '-fill' : '' }} me-1"></i>
          <span>{{ $tool->likes_count }}</span>
        </button>
      </form>

      <!-- Fork Form -->
      @auth
        <form action="{{ route('tools.fork', $tool->slug) }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-info">
            <i class="bi bi-git me-1"></i> Fork Tool
          </button>
        </form>
      @else
        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-info">
          <i class="bi bi-git me-1"></i> Fork
        </a>
      @endauth

      <!-- Embed Button -->
      <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#embedModal">
        <i class="bi bi-code-slash me-1"></i> Embed
      </button>

      <!-- Full Preview Launch -->
      <a href="{{ route('preview.show', $tool->slug) }}" target="_blank" class="btn btn-sm btn-primary">
        <i class="bi bi-box-arrow-up-right me-1"></i> Fullscreen
      </a>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Live Preview & File Inspector -->
    <div class="col-lg-8">
      <!-- Live Preview Container -->
      <div class="card border shadow-sm mb-4 overflow-hidden">
        <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2">
          <div class="d-flex align-items-center gap-2 small">
            <span class="badge bg-success-subtle text-success font-mono">SANDBOXED RUNTIME</span>
            <span class="text-muted">Live Preview</span>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary py-0" onclick="document.getElementById('previewIframe').src = document.getElementById('previewIframe').src">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
        </div>
        <div class="card-body p-0" style="height: 500px; background: #0f172a;">
          <iframe id="previewIframe" src="{{ route('preview.show', $tool->slug) }}" class="w-100 h-100 border-0" sandbox="allow-scripts allow-forms allow-modals"></iframe>
        </div>
      </div>

      <!-- Description & Usage -->
      <div class="card border p-4 mb-4">
        <h5 class="fw-bold mb-3">About this Tool</h5>
        <p class="text-muted">{{ $tool->description }}</p>
      </div>

      <!-- Files Tree -->
      <div class="card border p-4 mb-4">
        <h5 class="fw-bold mb-3">Workspace Files ({{ $tool->files->count() }})</h5>
        <div class="list-group list-group-flush">
          @foreach($tool->files as $file)
            <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center py-2 px-0">
              <div class="d-flex align-items-center gap-2 font-mono small">
                <i class="bi bi-file-earmark-code text-info"></i>
                <span>{{ $file->path }}</span>
              </div>
              <span class="badge bg-body-tertiary text-muted font-mono small">{{ round($file->size_bytes / 1024, 1) }} KB</span>
            </div>
          @endforeach
        </div>
      </div>

      <!-- Comments Section -->
      <div class="card border p-4">
        <h5 class="fw-bold mb-3">Community Comments ({{ $tool->comments->count() }})</h5>
        @auth
          <form action="{{ route('tools.comment', $tool->slug) }}" method="POST" class="mb-4">
            @csrf
            <div class="mb-2">
              <textarea name="content" class="form-control bg-body-tertiary" rows="3" placeholder="Leave constructive feedback or request features..."></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-gradient">Post Comment</button>
          </form>
        @else
          <div class="alert alert-secondary py-2 small mb-4">
            <a href="{{ route('login') }}" class="fw-bold text-info">Sign in</a> to leave a comment or feedback.
          </div>
        @endauth

        <div class="d-flex flex-column gap-3">
          @forelse($tool->comments as $comment)
            <div class="border-bottom pb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-semibold small">{{ $comment->user->name }}</span>
                <span class="text-muted small">{{ $comment->created_at->diffForHumans() }}</span>
              </div>
              <p class="text-muted small mb-0">{{ $comment->content }}</p>
            </div>
          @empty
            <p class="text-muted small mb-0">No comments yet. Start the conversation!</p>
          @endforelse
        </div>
      </div>
    </div>

    <!-- Right Column: Sidebar Stats & Creator Info -->
    <div class="col-lg-4">
      <div class="card border p-3 mb-4">
        <h6 class="fw-bold mb-3">Tool Details</h6>
        <dl class="row mb-0 small">
          <dt class="col-6 text-muted">Category:</dt>
          <dd class="col-6 text-end">{{ $tool->category?->name ?? 'Utility' }}</dd>

          <dt class="col-6 text-muted">Version:</dt>
          <dd class="col-6 text-end font-mono">{{ $tool->current_version }}</dd>

          <dt class="col-6 text-muted">Total Views:</dt>
          <dd class="col-6 text-end">{{ $tool->views_count }}</dd>

          <dt class="col-6 text-muted">Forks:</dt>
          <dd class="col-6 text-end">{{ $tool->forks_count }}</dd>

          <dt class="col-6 text-muted">Storage Size:</dt>
          <dd class="col-6 text-end font-mono">{{ round($tool->storage_bytes / 1024, 1) }} KB</dd>
        </dl>
      </div>

      <!-- Creator Card -->
      <div class="card border p-3 mb-4 text-center">
        <div class="fs-1 text-info mb-2"><i class="bi bi-person-circle"></i></div>
        <h6 class="fw-bold mb-1">{{ $tool->user->name }}</h6>
        <p class="text-muted small mb-2">{{ $tool->user->bio ?? 'Community developer.' }}</p>
        <a href="{{ route('profile.show', $tool->user->username ?? $tool->user->id) }}" class="btn btn-sm btn-outline-secondary">
          View Creator Profile
        </a>
      </div>

      <!-- ChatGPT Instruction Card -->
      <div class="card border border-info-subtle bg-info-subtle bg-opacity-10 p-3">
        <h6 class="fw-bold text-info mb-2"><i class="bi bi-robot me-1"></i> Edit with ChatGPT</h6>
        <p class="text-muted small mb-2">
          Connect ChatGPT via MCP and tell it:
        </p>
        <code class="d-block p-2 rounded bg-body font-mono small text-info mb-2">
          "Fork and customize {{ $tool->name }} from MicroTool Hub."
        </code>
        <a href="{{ route('dashboard.tokens') }}" class="btn btn-sm btn-outline-info w-100">Get MCP Connector</a>
      </div>
    </div>
  </div>
</div>

<!-- Embed Modal -->
<div class="modal fade" id="embedModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Embed this Micro-Tool</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-2">Copy this HTML iframe snippet into your website, blog, or documentation:</p>
        <textarea id="embedCodeText" class="form-control font-mono small bg-body-tertiary" rows="3" readonly><iframe src="{{ route('preview.show', $tool->slug) }}" width="100%" height="450" style="border:1px solid #334155; border-radius:8px;" sandbox="allow-scripts allow-forms allow-modals"></iframe></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('embedCodeText').value); this.textContent = 'Copied!';">Copy Snippet</button>
      </div>
    </div>
  </div>
</div>
@endsection
