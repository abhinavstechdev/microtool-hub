@extends('layouts.app')

@section('title', $tool->name . ' — Free Online Micro-Tool — MicroTool Hub')
@section('meta_description', Str::limit($tool->description, 160))

@section('content')
<div class="container py-4">
  <!-- Breadcrumb Navigation -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-secondary text-decoration-none"><i class="bi bi-house-door"></i> Home</a></li>
      @if($tool->category)
        <li class="breadcrumb-item"><a href="{{ route('explore', ['category' => $tool->category->slug]) }}" class="text-secondary text-decoration-none">{{ $tool->category->name }}</a></li>
      @endif
      <li class="breadcrumb-item active text-body fw-medium" aria-current="page">{{ $tool->name }}</li>
    </ol>
  </nav>

  <!-- Tool Workspace Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom" style="border-color: var(--border-subtle) !important;">
    <div>
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge py-1 px-2 font-mono small" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
          <i class="bi {{ $tool->category?->icon ?? 'bi-tools' }} me-1"></i> {{ $tool->category?->name ?? 'Utility' }}
        </span>
        <span class="badge py-1 px-2 font-mono small text-secondary bg-body-tertiary border">
          {{ $tool->current_version }}
        </span>
        @if($tool->fork_of_id)
          <span class="badge py-1 px-2 font-mono small text-warning bg-warning-subtle border border-warning-subtle">
            <i class="bi bi-git me-1"></i>Forked Tool
          </span>
        @endif
        <span class="badge py-1 px-2 font-mono small text-success bg-success-subtle border border-success-subtle">
          <i class="bi bi-shield-check me-1"></i>Client-Side Isolated
        </span>
      </div>

      <h1 class="h2 fw-bold mb-1 text-body">{{ $tool->name }}</h1>
      <p class="text-secondary small mb-0">
        Created by <a href="{{ route('profile.show', $tool->user->username ?? $tool->user->id) }}" class="fw-semibold text-decoration-none" style="color: var(--violet-500);">{{ $tool->user->name }}</a> 
        &bull; Published {{ $tool->published_at?->diffForHumans() ?? 'recently' }}
      </p>
    </div>

    <!-- Quick Actions Bar -->
    <div class="d-flex flex-wrap align-items-center gap-2">
      <!-- Like Button Form -->
      <form action="{{ route('tools.like', $tool->slug) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-sm {{ $isLiked ? 'btn-danger' : 'btn-secondary-custom' }} d-inline-flex align-items-center gap-1 shadow-none" title="Like this tool">
          <i class="bi bi-heart{{ $isLiked ? '-fill' : '' }} text-danger"></i>
          <span>{{ $tool->likes_count }}</span>
        </button>
      </form>

      <!-- Fork Button Form -->
      @auth
        <form action="{{ route('tools.fork', $tool->slug) }}" method="POST">
          @csrf
          <button type="submit" class="btn-outline-violet btn-sm text-decoration-none d-inline-flex align-items-center gap-1">
            <i class="bi bi-git"></i> Fork Tool
          </button>
        </form>
      @else
        <a href="{{ route('login') }}" class="btn-outline-violet btn-sm text-decoration-none d-inline-flex align-items-center gap-1">
          <i class="bi bi-git"></i> Fork Tool
        </a>
      @endauth

      <!-- Embed Modal Button -->
      <button class="btn-secondary-custom btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#embedModal">
        <i class="bi bi-code-slash"></i> Embed
      </button>

      <!-- Fullscreen / External Preview -->
      <a href="{{ route('preview.show', $tool->slug) }}" target="_blank" class="btn-violet btn-sm text-decoration-none d-inline-flex align-items-center gap-1" title="Open in new window">
        <i class="bi bi-box-arrow-up-right"></i> Fullscreen
      </a>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Tool Main Workspace & Info -->
    <div class="col-lg-8">
      <!-- Clean Light-Mode Workspace Container -->
      <div class="workspace-wrapper mb-4">
        <!-- Interactive Workspace Toolbar -->
        <div class="workspace-toolbar">
          <div class="d-flex align-items-center gap-2">
            <span class="badge py-1 px-2 font-mono small text-success bg-success-subtle border border-success-subtle">
              <i class="bi bi-cpu-fill me-1"></i>ACTIVE RUNTIME
            </span>
            <span class="text-secondary small d-none d-sm-inline">Browser Isolated Sandbox</span>
          </div>

          <!-- Device Simulation Switcher -->
          <div class="d-flex align-items-center gap-2">
            <div class="device-preview-controls">
              <button class="device-btn active" id="btnModeDesktop" title="Desktop View (100%)">
                <i class="bi bi-laptop"></i>
              </button>
              <button class="device-btn" id="btnModeTablet" title="Tablet View (768px)">
                <i class="bi bi-tablet"></i>
              </button>
              <button class="device-btn" id="btnModeMobile" title="Mobile View (390px)">
                <i class="bi bi-phone"></i>
              </button>
            </div>

            <!-- Reload Sandbox Button -->
            <button class="btn-secondary-custom py-1 px-2 small" id="btnReloadPreview" title="Reload Tool Sandbox">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
        </div>

        <!-- Working Area Canvas -->
        <div class="workspace-canvas preview-mode-desktop" id="workspaceCanvasContainer">
          <div class="iframe-container">
            <iframe 
              id="previewIframe" 
              src="{{ route('preview.show', $tool->slug) }}" 
              sandbox="allow-scripts allow-forms allow-modals allow-downloads" 
              title="Tool Preview: {{ $tool->name }}"
              loading="lazy"
            ></iframe>
          </div>
        </div>
      </div>

      <!-- About & How To Use Card -->
      <div class="bento-card mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="bento-icon-box mb-0" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="bi bi-info-circle-fill"></i>
          </div>
          <h2 class="h5 fw-bold mb-0">About This Tool</h2>
        </div>
        <p class="text-secondary small mb-3" style="line-height: 1.6;">
          {{ $tool->description }}
        </p>

        <!-- Instructions Pill List -->
        <div class="p-3 rounded-3 border" style="background: var(--bg-surface); border-color: var(--border-subtle) !important;">
          <h6 class="fw-bold small mb-2 text-body"><i class="bi bi-check2-circle text-success me-1"></i> How to use this tool:</h6>
          <ol class="small text-secondary mb-0 ps-3">
            <li class="mb-1">Input your values, drop files, or configure conversion settings above.</li>
            <li class="mb-1">All calculations and conversions execute 100% inside your web browser.</li>
            <li>Copy the generated results or download converted files directly to your device.</li>
          </ol>
        </div>
      </div>

      <!-- Workspace Files List Card -->
      <div class="bento-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <div class="bento-icon-box mb-0" style="width: 36px; height: 36px; font-size: 1rem;">
              <i class="bi bi-folder2-open"></i>
            </div>
            <h2 class="h5 fw-bold mb-0">Workspace Source Assets</h2>
          </div>
          <span class="badge font-mono small text-secondary bg-body-tertiary border">
            {{ $tool->files->count() }} file(s)
          </span>
        </div>

        <div class="list-group list-group-flush border rounded-3 overflow-hidden" style="border-color: var(--border-subtle) !important;">
          @foreach($tool->files as $file)
            <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center py-2 px-3" style="border-color: var(--border-subtle) !important;">
              <div class="d-flex align-items-center gap-2 font-mono small">
                <i class="bi bi-file-earmark-code" style="color: var(--violet-500);"></i>
                <span class="text-body fw-medium">{{ $file->path }}</span>
              </div>
              <span class="badge font-mono small text-secondary bg-body-tertiary border">
                {{ round($file->size_bytes / 1024, 1) }} KB
              </span>
            </div>
          @endforeach
        </div>
      </div>

      <!-- FAQ Section -->
      <div class="bento-card mb-4">
        <h2 class="h5 fw-bold mb-3">Frequently Asked Questions</h2>
        <div class="accordion accordion-flush" id="toolFaqAccordion">
          <div class="accordion-item bg-transparent border-0 border-bottom" style="border-color: var(--border-subtle) !important;">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent text-body shadow-none px-0 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                Are my files or inputs uploaded to any server?
              </button>
            </h3>
            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#toolFaqAccordion">
              <div class="accordion-body px-0 text-secondary small pt-0 pb-3">
                No. All file processing, parsing, image operations, and calculations execute entirely in your local web browser. Our Content Security Policy strictly blocks outbound data exfiltration.
              </div>
            </div>
          </div>

          <div class="accordion-item bg-transparent border-0 border-bottom" style="border-color: var(--border-subtle) !important;">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed bg-transparent text-body shadow-none px-0 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                Can I customize or modify this tool?
              </button>
            </h3>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#toolFaqAccordion">
              <div class="accordion-body px-0 text-secondary small pt-0 pb-3">
                Yes! Simply click <strong>Fork Tool</strong> to duplicate the code into your personal account. You can then edit the HTML, CSS, and JavaScript in AI Code Studio or instruct ChatGPT via MCP.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Community Feedback & Comments -->
      <div class="bento-card">
        <h2 class="h5 fw-bold mb-3">Community Feedback ({{ $tool->comments->count() }})</h2>
        @auth
          <form action="{{ route('tools.comment', $tool->slug) }}" method="POST" class="mb-4">
            @csrf
            <div class="mb-2">
              <textarea name="content" class="form-control text-body shadow-none" rows="3" placeholder="Leave constructive feedback or request feature enhancements..." style="background: var(--bg-surface); border-color: var(--border-subtle);"></textarea>
            </div>
            <button type="submit" class="btn-violet btn-sm">Post Comment</button>
          </form>
        @else
          <div class="p-3 rounded-3 mb-4 border text-center" style="background: var(--bg-surface); border-color: var(--border-subtle) !important;">
            <p class="text-secondary small mb-2">Want to leave feedback or report an issue?</p>
            <a href="{{ route('login') }}" class="btn-secondary-custom btn-sm text-decoration-none">Sign in to leave a comment</a>
          </div>
        @endauth

        <div class="d-flex flex-column gap-3">
          @forelse($tool->comments as $comment)
            <div class="border-bottom pb-3" style="border-color: var(--border-subtle) !important;">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-semibold small text-body">{{ $comment->user->name }}</span>
                <span class="text-secondary small">{{ $comment->created_at->diffForHumans() }}</span>
              </div>
              <p class="text-secondary small mb-0">{{ $comment->content }}</p>
            </div>
          @empty
            <p class="text-secondary small mb-0">No comments yet. Be the first to share your experience!</p>
          @endforelse
        </div>
      </div>
    </div>

    <!-- Right Column: Metadata, Creator & AI Connect -->
    <div class="col-lg-4">
      <!-- Tool Metadata Card -->
      <div class="bento-card mb-4">
        <h3 class="h6 fw-bold mb-3 text-body">Tool Specifications</h3>
        <dl class="row mb-0 small text-secondary">
          <dt class="col-6 fw-normal">Category:</dt>
          <dd class="col-6 text-end text-body fw-medium">{{ $tool->category?->name ?? 'Utility' }}</dd>

          <dt class="col-6 fw-normal">Version:</dt>
          <dd class="col-6 text-end font-mono text-body">{{ $tool->current_version }}</dd>

          <dt class="col-6 fw-normal">Total Views:</dt>
          <dd class="col-6 text-end text-body">{{ $tool->views_count }}</dd>

          <dt class="col-6 fw-normal">Community Forks:</dt>
          <dd class="col-6 text-end text-body">{{ $tool->forks_count }}</dd>

          <dt class="col-6 fw-normal">Storage Quota:</dt>
          <dd class="col-6 text-end font-mono text-body">{{ round($tool->storage_bytes / 1024, 1) }} KB</dd>

          <dt class="col-6 fw-normal">Execution:</dt>
          <dd class="col-6 text-end text-success fw-medium">Client-Side</dd>
        </dl>
      </div>

      <!-- Creator Profile Card -->
      <div class="bento-card mb-4 text-center">
        <div class="bento-icon-box mx-auto mb-2" style="width: 52px; height: 52px; font-size: 1.5rem;">
          <i class="bi bi-person-circle"></i>
        </div>
        <h4 class="h6 fw-bold mb-1 text-body">{{ $tool->user->name }}</h4>
        <p class="text-secondary small mb-3">{{ $tool->user->bio ?? 'Community micro-tool creator.' }}</p>
        <a href="{{ route('profile.show', $tool->user->username ?? $tool->user->id) }}" class="btn-secondary-custom btn-sm text-decoration-none w-100">
          View Creator Catalog
        </a>
      </div>

      <!-- Related Tools in same category -->
      @if(isset($relatedTools) && $relatedTools->isNotEmpty())
        <div class="bento-card mb-4">
          <h3 class="h6 fw-bold mb-3 text-body">Related Tools</h3>
          <div class="d-flex flex-column gap-2">
            @foreach($relatedTools as $rel)
              <a href="{{ route('tools.show', $rel->slug) }}" class="p-2 rounded-2 text-decoration-none border d-flex align-items-center justify-content-between" style="background: var(--bg-surface); border-color: var(--border-subtle) !important;">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi {{ $rel->category?->icon ?? 'bi-tools' }}" style="color: var(--violet-500);"></i>
                  <span class="small fw-semibold text-body">{{ $rel->name }}</span>
                </div>
                <i class="bi bi-chevron-right text-secondary small"></i>
              </a>
            @endforeach
          </div>
        </div>
      @endif

      <!-- ChatGPT MCP Prompt Card -->
      <div class="bento-card" style="background: linear-gradient(135deg, var(--bg-card) 0%, rgba(139, 92, 246, 0.08) 100%); border-color: var(--violet-border);">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="mcp-pill">AI ASSISTANT</span>
          <span class="text-secondary small">Edit with ChatGPT</span>
        </div>
        <p class="text-secondary small mb-2" style="line-height: 1.5;">
          Ask ChatGPT to enhance this tool or inspect its source code:
        </p>
        <code class="d-block p-2 rounded font-mono small text-break mb-3" style="background: var(--bg-secondary); color: var(--violet-500); border: 1px solid var(--border-subtle);">
          "Inspect {{ $tool->name }} on MicroTool Hub and add extra export options."
        </code>
        <a href="{{ route('dashboard.tokens') }}" class="btn-violet btn-sm text-decoration-none w-100 text-center">
          <i class="bi bi-cpu"></i> Get MCP Connector Token
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Embed Modal -->
<div class="modal fade" id="embedModal" tabindex="-1" aria-labelledby="embedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bento-card border-0 p-0 overflow-hidden shadow-lg">
      <div class="modal-header border-bottom py-3 px-4" style="border-color: var(--border-subtle) !important;">
        <h5 class="modal-title h6 fw-bold" id="embedModalLabel">Embed {{ $tool->name }}</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-secondary small mb-2">Copy this responsive sandbox iframe code into your website or docs:</p>
        <textarea id="embedCodeText" class="form-control font-mono small mb-3 text-body shadow-none" rows="3" readonly style="background: var(--bg-surface); border-color: var(--border-subtle);"><iframe src="{{ route('preview.show', $tool->slug) }}" width="100%" height="520" style="border:1px solid #e2e8f0; border-radius:12px;" sandbox="allow-scripts allow-forms allow-modals allow-downloads"></iframe></textarea>
      </div>
      <div class="modal-footer border-top py-2 px-4" style="border-color: var(--border-subtle) !important;">
        <button type="button" class="btn-secondary-custom btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn-violet btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('embedCodeText').value); this.innerHTML = '<i class=\'bi bi-check2\'></i> Copied!'; setTimeout(() => this.textContent = 'Copy Code', 2000);">Copy Code</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  (function() {
    const canvas = document.getElementById('workspaceCanvasContainer');
    const iframe = document.getElementById('previewIframe');
    const btnDesktop = document.getElementById('btnModeDesktop');
    const btnTablet = document.getElementById('btnModeTablet');
    const btnMobile = document.getElementById('btnModeMobile');
    const btnReload = document.getElementById('btnReloadPreview');

    function setMode(mode, activeBtn) {
      if (!canvas) return;
      canvas.className = 'workspace-canvas preview-mode-' + mode;
      [btnDesktop, btnTablet, btnMobile].forEach(b => b && b.classList.remove('active'));
      if (activeBtn) activeBtn.classList.add('active');
    }

    if (btnDesktop) btnDesktop.addEventListener('click', () => setMode('desktop', btnDesktop));
    if (btnTablet) btnTablet.addEventListener('click', () => setMode('tablet', btnTablet));
    if (btnMobile) btnMobile.addEventListener('click', () => setMode('mobile', btnMobile));
    if (btnReload && iframe) {
      btnReload.addEventListener('click', () => {
        iframe.src = iframe.src;
      });
    }
  })();
</script>
@endpush
@endsection
