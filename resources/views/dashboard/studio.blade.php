@extends('layouts.app')

@section('title', 'AI Code Studio — ' . $project->name)

@push('styles')
<style>
  .studio-layout {
    display: grid;
    grid-template-columns: 240px 1fr 480px;
    height: calc(100vh - 125px);
    overflow: hidden;
  }
  @media (max-width: 1200px) {
    .studio-layout {
      grid-template-columns: 200px 1fr 380px;
    }
  }
  @media (max-width: 992px) {
    .studio-layout {
      display: flex;
      flex-direction: column;
      height: auto;
      overflow: visible;
    }
  }
  .file-tree-item {
    cursor: pointer;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 0.85rem;
    transition: background 0.15s;
  }
  .file-tree-item:hover {
    background: rgba(255, 255, 255, 0.05);
  }
  .file-tree-item.active {
    background: rgba(6, 182, 212, 0.15);
    color: var(--mcp-cyan);
    font-weight: 600;
  }
  .editor-textarea {
    width: 100%;
    height: 100%;
    background: #090d16;
    color: #e2e8f0;
    border: none;
    outline: none;
    resize: none;
    padding: 16px;
    font-family: var(--font-mono);
    font-size: 0.9rem;
    line-height: 1.6;
    tab-size: 2;
  }
  .editor-textarea:focus {
    background: #070a12;
  }
</style>
@endpush

@section('content')
<!-- Studio Top Navigation -->
<div class="bg-body-tertiary border-bottom py-2 px-3 d-flex flex-wrap justify-content-between align-items-center">
  <div class="d-flex align-items-center gap-3">
    <a href="{{ route('dashboard.index') }}" class="btn btn-sm btn-outline-secondary" title="Back to Dashboard">
      <i class="bi bi-chevron-left"></i>
    </a>
    <div>
      <span class="fw-bold me-2">{{ $project->name }}</span>
      <span class="badge {{ $project->status === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} small font-mono">
        {{ $project->status }}
      </span>
      <span id="unsavedBadge" class="badge bg-danger small ms-1 d-none">Unsaved</span>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    <!-- Save Button -->
    <button id="saveBtn" class="btn btn-sm btn-primary">
      <i class="bi bi-floppy me-1"></i> Save <span class="d-none d-sm-inline font-mono small text-white-50">(Ctrl+S)</span>
    </button>

    <!-- Snapshot Button -->
    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#snapshotModal">
      <i class="bi bi-camera me-1"></i> Snapshot
    </button>

    <!-- Preview in New Tab -->
    <a href="{{ route('preview.show', $project->slug) }}" target="_blank" class="btn btn-sm btn-outline-info">
      <i class="bi bi-play-circle me-1"></i> Open Preview
    </a>

    <!-- Publish Toggle -->
    <form action="{{ route('dashboard.projects.publish', $project->uuid) }}" method="POST" class="d-inline">
      @csrf
      <button type="submit" class="btn btn-sm {{ $project->status === 'published' ? 'btn-outline-warning' : 'btn-success' }}">
        {{ $project->status === 'published' ? 'Unpublish' : 'Publish Tool' }}
      </button>
    </form>
  </div>
</div>

<!-- Studio 3-Panel Workspace -->
<div class="studio-layout">
  <!-- Left Panel: File Explorer & Snapshots -->
  <div class="border-end bg-body d-flex flex-column" style="overflow-y: auto;">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <span class="fw-bold small text-uppercase text-muted">Project Files</span>
      <button class="btn btn-sm btn-outline-info py-0 px-2" data-bs-toggle="modal" data-bs-target="#newFileModal" title="Add File">
        <i class="bi bi-plus-lg"></i>
      </button>
    </div>

    <!-- Files List -->
    <div class="p-2 flex-grow-1" id="fileTreeContainer">
      @foreach($files as $file)
        <div class="file-tree-item d-flex justify-content-between align-items-center {{ $activeFile && $activeFile->path === $file->path ? 'active' : '' }}" 
             data-path="{{ $file->path }}" 
             onclick="selectFile('{{ $file->path }}')">
          <div class="d-flex align-items-center gap-2 text-truncate">
            @if(in_array($file->extension, ['html', 'htm']))
              <i class="bi bi-filetype-html text-danger"></i>
            @elseif($file->extension === 'css')
              <i class="bi bi-filetype-css text-primary"></i>
            @elseif($file->extension === 'js')
              <i class="bi bi-filetype-js text-warning"></i>
            @elseif($file->extension === 'json')
              <i class="bi bi-filetype-json text-info"></i>
            @else
              <i class="bi bi-file-earmark-code text-secondary"></i>
            @endif
            <span class="text-truncate">{{ $file->path }}</span>
          </div>
          <span class="small text-muted font-mono" style="font-size: 0.75rem;">{{ round($file->size_bytes / 1024, 1) }}k</span>
        </div>
      @endforeach
    </div>

    <!-- Snapshots Mini Panel -->
    <div class="border-top p-3 bg-body-tertiary">
      <div class="fw-bold small text-uppercase text-muted mb-2">Snapshots ({{ $project->snapshots->count() }})</div>
      <div class="small text-muted mb-2">
        @forelse($project->snapshots->take(3) as $snap)
          <div class="d-flex justify-content-between py-1 border-bottom font-mono" style="font-size: 0.75rem;">
            <span class="text-info">{{ $snap->version_tag }}</span>
            <span>{{ $snap->created_at->format('M d H:i') }}</span>
          </div>
        @empty
          <span style="font-size: 0.75rem;">No snapshots saved yet.</span>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Center Panel: Code Editor -->
  <div class="d-flex flex-column border-end bg-body">
    <!-- Active File Tab Bar -->
    <div class="bg-body-tertiary border-bottom px-3 py-2 d-flex justify-content-between align-items-center small">
      <div class="d-flex align-items-center gap-2 font-mono">
        <i class="bi bi-code-slash text-info"></i>
        <strong id="activeFileName">{{ $activeFile->path ?? 'No file selected' }}</strong>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span id="saveStatus" class="text-muted small">Ready</span>
        @if($activeFile)
          <button id="deleteFileBtn" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete File" onclick="deleteActiveFile()">
            <i class="bi bi-trash"></i>
          </button>
        @endif
      </div>
    </div>

    <!-- Real-time Textarea Editor -->
    <div class="flex-grow-1 position-relative">
      <textarea id="codeEditor" class="editor-textarea" spellcheck="false">{{ $activeContent }}</textarea>
    </div>
  </div>

  <!-- Right Panel: Live Preview & Activity Logs -->
  <div class="d-flex flex-column bg-body">
    <!-- Right Panel Tabs -->
    <ul class="nav nav-tabs px-3 pt-2 bg-body-tertiary border-bottom" role="tablist">
      <li class="nav-item">
        <button class="nav-link active small py-1 px-3" data-bs-toggle="tab" data-bs-target="#tabPreview">
          <i class="bi bi-play-circle me-1"></i> Live Preview
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link small py-1 px-3" data-bs-toggle="tab" data-bs-target="#tabAudit">
          <i class="bi bi-robot me-1"></i> MCP Activity Log
        </button>
      </li>
    </ul>

    <div class="tab-content flex-grow-1 d-flex flex-column">
      <!-- Preview Tab -->
      <div class="tab-pane fade show active flex-grow-1 d-flex flex-column" id="tabPreview">
        <div class="bg-body-tertiary border-bottom px-3 py-1 d-flex justify-content-between align-items-center small">
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary py-0 active" onclick="setViewport('100%')"><i class="bi bi-display"></i></button>
            <button type="button" class="btn btn-outline-secondary py-0" onclick="setViewport('768px')"><i class="bi bi-tablet"></i></button>
            <button type="button" class="btn btn-outline-secondary py-0" onclick="setViewport('375px')"><i class="bi bi-phone"></i></button>
          </div>
          <button class="btn btn-sm btn-outline-secondary py-0" onclick="refreshPreview()" title="Reload Preview">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
        </div>
        <div class="flex-grow-1 d-flex justify-content-center align-items-stretch" style="background: #0f172a; overflow: auto;">
          <iframe id="studioPreviewIframe" 
                  src="{{ route('preview.show', $project->slug) }}" 
                  class="h-100 border-0" 
                  style="width: 100%; transition: width 0.2s;"
                  sandbox="allow-scripts allow-forms allow-modals"></iframe>
        </div>
      </div>

      <!-- Audit & MCP Tab -->
      <div class="tab-pane fade flex-grow-1 p-3 font-mono small" id="tabAudit" style="overflow-y: auto;">
        <h6 class="fw-bold mb-3 font-sans text-muted">MCP Activity & Audit Events</h6>
        @forelse($auditLogs as $log)
          <div class="mb-2 pb-2 border-bottom">
            <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
              <span class="text-info">{{ $log->action }}</span>
              <span>{{ $log->created_at->format('H:i:s') }}</span>
            </div>
            @if($log->payload)
              <div class="text-muted text-truncate" style="font-size: 0.75rem;">
                {{ json_encode($log->payload) }}
              </div>
            @endif
          </div>
        @empty
          <p class="text-muted">No MCP actions recorded yet. Connect ChatGPT to see live tool invocations.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>

<!-- New File Modal -->
<div class="modal fade" id="newFileModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create New File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label small fw-semibold">Relative Path</label>
        <input type="text" id="newFilePathInput" class="form-control font-mono" placeholder="e.g. helpers.js or css/theme.css">
        <div class="form-text small">Allowed: .html, .css, .js, .json, .svg, .png, .md, .txt</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="createNewFile()">Create File</button>
      </div>
    </div>
  </div>
</div>

<!-- Snapshot Modal -->
<div class="modal fade" id="snapshotModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Project Snapshot</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Version Tag</label>
          <input type="text" id="snapTagInput" class="form-control font-mono" value="v1.0.{{ $project->snapshots->count() + 1 }}">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Label / Note</label>
          <input type="text" id="snapLabelInput" class="form-control" placeholder="e.g. Added dark mode toggle">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="createSnapshot()">Create Snapshot</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  let currentFile = '{{ $activeFile->path ?? "" }}';
  let isDirty = false;
  const projectUuid = '{{ $project->uuid }}';
  const editor = document.getElementById('codeEditor');
  const unsavedBadge = document.getElementById('unsavedBadge');
  const saveStatus = document.getElementById('saveStatus');

  // Handle Tab indentation
  editor.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
      e.preventDefault();
      const start = editor.selectionStart;
      const end = editor.selectionEnd;
      editor.value = editor.value.substring(0, start) + "  " + editor.value.substring(end);
      editor.selectionStart = editor.selectionEnd = start + 2;
      triggerDirty();
    }
    // Ctrl+S / Cmd+S
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault();
      saveCurrentFile();
    }
  });

  editor.addEventListener('input', triggerDirty);

  function triggerDirty() {
    isDirty = true;
    unsavedBadge.classList.remove('d-none');
    saveStatus.textContent = 'Unsaved changes';
  }

  function saveCurrentFile() {
    if (!currentFile) return;

    saveStatus.textContent = 'Saving…';
    fetch(`/studio/${projectUuid}/file`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({
        path: currentFile,
        content: editor.value
      })
    })
    .then(r => r.json())
    .then(data => {
      isDirty = false;
      unsavedBadge.classList.add('d-none');
      saveStatus.textContent = 'Saved at ' + new Date().toLocaleTimeString();
      refreshPreview();
    })
    .catch(err => {
      saveStatus.textContent = 'Error saving!';
      alert('Failed to save file: ' + err.message);
    });
  }

  document.getElementById('saveBtn').addEventListener('click', saveCurrentFile);

  function selectFile(path) {
    if (isDirty) {
      if (!confirm('You have unsaved changes. Switch file anyway?')) return;
    }

    currentFile = path;
    document.getElementById('activeFileName').textContent = path;
    
    // Update active class in sidebar
    document.querySelectorAll('.file-tree-item').forEach(el => {
      el.classList.toggle('active', el.getAttribute('data-path') === path);
    });

    saveStatus.textContent = 'Loading…';
    fetch(`/studio/${projectUuid}/file?path=${encodeURIComponent(path)}`)
      .then(r => r.json())
      .then(data => {
        editor.value = data.content;
        isDirty = false;
        unsavedBadge.classList.add('d-none');
        saveStatus.textContent = 'Ready';
      });
  }

  function refreshPreview() {
    const iframe = document.getElementById('studioPreviewIframe');
    iframe.src = iframe.src;
  }

  function setViewport(width) {
    const iframe = document.getElementById('studioPreviewIframe');
    iframe.style.width = width;
  }

  function createNewFile() {
    const path = document.getElementById('newFilePathInput').value.trim();
    if (!path) return;

    fetch(`/studio/${projectUuid}/file/create`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ path: path, content: '' })
    })
    .then(r => r.json())
    .then(() => {
      location.reload();
    })
    .catch(err => alert(err.message));
  }

  function deleteActiveFile() {
    if (!currentFile) return;
    if (!confirm(`Delete ${currentFile}? This cannot be undone.`)) return;

    fetch(`/studio/${projectUuid}/file`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ path: currentFile })
    })
    .then(r => r.json())
    .then(() => location.reload());
  }

  function createSnapshot() {
    const tag = document.getElementById('snapTagInput').value.trim();
    const label = document.getElementById('snapLabelInput').value.trim();

    fetch(`/studio/${projectUuid}/snapshot`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ version_tag: tag, label: label })
    })
    .then(r => r.json())
    .then(() => location.reload());
  }
</script>
@endpush
