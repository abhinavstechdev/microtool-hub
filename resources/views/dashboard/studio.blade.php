@extends('layouts.app')

@section('title', 'AI Code Studio — ' . $project->name)
@section('body_class', 'studio-body')
@section('navbar_container_class', 'container-fluid px-3')
@section('hide_flash', true)
@section('hide_footer', true)

@push('styles')
  <!-- CodeMirror 5 Stylesheets for Professional IDE Experience -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/dialog/dialog.min.css">
@endpush

@section('content')
<div class="studio-root" id="studioRoot">

  <!-- 1. Studio Project Toolbar -->
  <header class="studio-toolbar">
    <!-- Left Cluster: Navigation & Project Meta -->
    <div class="d-flex align-items-center gap-2 text-truncate me-2">
      <a href="{{ route('dashboard.index') }}" class="studio-btn studio-btn-ghost" title="Back to Projects Dashboard">
        <i class="bi bi-chevron-left"></i>
        <span class="d-none d-sm-inline">Projects</span>
      </a>

      <span class="text-secondary opacity-50">&bull;</span>

      <div class="d-flex align-items-center gap-2 text-truncate">
        <span class="fw-bold text-body text-truncate" style="max-width: 220px;" title="{{ $project->name }}">
          {{ $project->name }}
        </span>

        @if($project->status === 'published')
          <span class="badge bg-success-subtle text-success border border-success-subtle font-mono small py-1 px-2 d-none d-md-inline-block">
            <i class="bi bi-check-circle-fill me-1"></i>Published
          </span>
        @elseif($project->status === 'pending_approval')
          <span class="badge bg-warning text-dark font-mono small py-1 px-2 d-none d-md-inline-block">
            <i class="bi bi-hourglass-split me-1"></i>Under Review
          </span>
        @elseif($project->status === 'rejected')
          <span class="badge bg-danger text-white font-mono small py-1 px-2 d-none d-md-inline-block">
            <i class="bi bi-x-circle-fill me-1"></i>Rejected
          </span>
        @else
          <span class="badge bg-secondary-subtle text-secondary border font-mono small py-1 px-2 d-none d-md-inline-block">
            Draft
          </span>
        @endif

        <span id="unsavedBadge" class="badge bg-warning text-dark font-mono small ms-1 d-none">
          <i class="bi bi-dot"></i>Unsaved
        </span>

        <span id="saveStatus" class="small text-secondary font-mono ms-1 d-none d-xl-inline">Ready</span>
      </div>
    </div>

    <!-- Right Cluster: Primary Actions -->
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
      <!-- Save Button -->
      <button id="saveBtn" class="studio-btn studio-btn-primary" title="Save Active File (Ctrl+S)">
        <i class="bi bi-floppy2"></i>
        <span>Save</span>
        <kbd class="studio-kbd d-none d-sm-inline">Ctrl+S</kbd>
      </button>

      <!-- Snapshot Button -->
      <button class="studio-btn studio-btn-secondary" data-bs-toggle="modal" data-bs-target="#snapshotModal" title="Create Version Snapshot Backup">
        <i class="bi bi-camera"></i>
        <span class="d-none d-sm-inline">Snapshot</span>
      </button>

      <!-- Open Preview in New Tab -->
      <a href="{{ route('preview.show', $project->slug) }}" target="_blank" class="studio-btn studio-btn-secondary" title="Open Preview in New Tab">
        <i class="bi bi-box-arrow-up-right"></i>
        <span class="d-none d-md-inline">Preview</span>
      </a>

      <!-- Publish / Review Submission Toggle -->
      <form action="{{ route('dashboard.projects.publish', $project->uuid) }}" method="POST" class="d-inline">
        @csrf
        @if($project->status === 'published')
          <button type="submit" class="studio-btn studio-btn-warning" title="Withdraw from Public Catalog">
            <i class="bi bi-eye-slash"></i>
            <span class="d-none d-sm-inline">Unpublish</span>
          </button>
        @elseif($project->status === 'pending_approval')
          <button type="submit" class="studio-btn studio-btn-danger" title="Withdraw Submission Back to Draft">
            <i class="bi bi-x-circle"></i>
            <span class="d-none d-sm-inline">Cancel Review</span>
          </button>
        @else
          <button type="submit" class="studio-btn studio-btn-success" title="{{ auth()->user()->role === 'admin' ? 'Publish Immediately to Public Catalog' : 'Submit for Admin Approval' }}">
            <i class="bi bi-send-check"></i>
            <span>{{ auth()->user()->role === 'admin' ? 'Publish' : 'Submit Tool' }}</span>
          </button>
        @endif
      </form>
    </div>
  </header>

  <!-- 2. Mobile Tab Switcher (< 768px) -->
  <nav class="studio-mobile-nav" id="studioMobileNav">
    <button class="studio-mobile-btn active" data-tab="editor" onclick="switchMobileTab('editor')">
      <i class="bi bi-code-slash"></i> Editor
    </button>
    <button class="studio-mobile-btn" data-tab="files" onclick="switchMobileTab('files')">
      <i class="bi bi-folder2-open"></i> Files (<span id="mobileFileCount">{{ $files->count() }}</span>)
    </button>
    <button class="studio-mobile-btn" data-tab="preview" onclick="switchMobileTab('preview')">
      <i class="bi bi-play-circle"></i> Preview
    </button>
    <button class="studio-mobile-btn" data-tab="mcp" onclick="switchMobileTab('mcp')">
      <i class="bi bi-cpu"></i> MCP Log
    </button>
  </nav>

  <!-- 3. Studio 3-Panel Workspace -->
  <div class="studio-workspace" id="studioWorkspace">

    <!-- Panel 1: Left Sidebar (Project Files & Snapshots) -->
    <aside class="studio-sidebar" id="studioSidebar">
      <!-- Sidebar Header -->
      <div class="p-2 px-3 border-bottom d-flex justify-content-between align-items-center bg-body-tertiary">
        <span class="fw-bold small text-uppercase letter-spacing-1 text-secondary" style="font-size: 0.75rem;">
          <i class="bi bi-folder2-open me-1"></i> Project Files
        </span>
        <div class="d-flex align-items-center gap-1">
          <button class="studio-editor-tool" data-bs-toggle="modal" data-bs-target="#newFileModal" title="Add File">
            <i class="bi bi-plus-lg"></i>
          </button>
          <button id="collapseSidebarBtn" class="studio-editor-tool d-none d-lg-inline-flex" title="Collapse Sidebar">
            <i class="bi bi-layout-sidebar"></i>
          </button>
        </div>
      </div>

      <!-- File Tree List -->
      <div class="p-2 flex-grow-1 overflow-y-auto" id="fileTreeContainer">
        @foreach($files as $file)
          <div class="file-tree-item {{ $activeFile && $activeFile->path === $file->path ? 'active' : '' }}" 
               data-path="{{ $file->path }}" 
               onclick="selectFile('{{ $file->path }}')"
               title="{{ $file->path }} ({{ round($file->size_bytes / 1024, 1) }} KB)">
            <div class="d-flex align-items-center gap-2 text-truncate">
              @if(in_array($file->extension, ['html', 'htm']))
                <i class="bi bi-filetype-html fs-6" style="color: #f97316;"></i>
              @elseif($file->extension === 'css')
                <i class="bi bi-filetype-css fs-6" style="color: #38bdf8;"></i>
              @elseif($file->extension === 'js')
                <i class="bi bi-filetype-js fs-6" style="color: #facc15;"></i>
              @elseif($file->extension === 'json')
                <i class="bi bi-filetype-json fs-6" style="color: #4ade80;"></i>
              @elseif(in_array($file->extension, ['png', 'jpg', 'jpeg', 'svg', 'webp']))
                <i class="bi bi-file-earmark-image fs-6" style="color: #c084fc;"></i>
              @else
                <i class="bi bi-file-earmark-code fs-6 text-secondary"></i>
              @endif
              <span class="text-truncate">{{ $file->path }}</span>
            </div>
            <span class="small text-muted font-mono ms-2 flex-shrink-0" style="font-size: 0.72rem;">
              {{ round($file->size_bytes / 1024, 1) }}k
            </span>
          </div>
        @endforeach
      </div>

      <!-- Snapshots Accordion / Bottom Section -->
      <div class="border-top p-2 px-3 bg-body-tertiary">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="fw-bold small text-uppercase letter-spacing-1 text-secondary" style="font-size: 0.72rem;">
            Snapshots ({{ $project->snapshots->count() }})
          </span>
          <button class="btn btn-link p-0 text-secondary small text-decoration-none" data-bs-toggle="modal" data-bs-target="#snapshotModal" title="Take Snapshot">
            <i class="bi bi-camera"></i>
          </button>
        </div>
        <div class="small text-muted" style="max-height: 90px; overflow-y: auto;">
          @forelse($project->snapshots->take(4) as $snap)
            <div class="d-flex justify-content-between py-1 border-bottom border-subtle font-mono" style="font-size: 0.72rem;">
              <span class="text-truncate" style="max-width: 110px; color: var(--violet-500);" title="{{ $snap->version_tag }} — {{ $snap->label }}">
                {{ $snap->version_tag }}
              </span>
              <span class="text-secondary">{{ $snap->created_at->format('M d H:i') }}</span>
            </div>
          @empty
            <span class="text-secondary" style="font-size: 0.72rem;">No snapshots saved yet.</span>
          @endforelse
        </div>
      </div>
    </aside>

    <!-- Resizer 1 (Left Sidebar <-> Center Editor) -->
    <div class="studio-resizer" id="sidebarResizer" title="Drag to resize sidebar (double-click to reset)"></div>

    <!-- Panel 2: Center Code Editor -->
    <main class="studio-editor-panel mobile-active" id="studioEditorPanel">
      <!-- Editor Header & Tabs -->
      <div class="studio-editor-tabbar">
        <div class="d-flex align-items-center gap-2">
          <!-- Collapsed Sidebar Trigger -->
          <button id="expandSidebarBtn" class="studio-editor-tool d-none" title="Expand Sidebar">
            <i class="bi bi-layout-sidebar"></i>
          </button>

          <!-- Active File Tab -->
          <div class="studio-tab-active" id="activeFileTab">
            <i class="bi bi-code-slash text-info" id="activeFileIcon"></i>
            <span id="activeFileName">{{ $activeFile->path ?? 'No file selected' }}</span>
            <span id="tabDirtyDot" class="d-none" style="color: var(--violet-500);">&bull;</span>
          </div>
        </div>

        <!-- Editor Toolbar Tools -->
        <div class="d-flex align-items-center gap-1">
          <!-- Toggle Word Wrap -->
          <button id="toggleWrapBtn" class="studio-editor-tool" title="Toggle Word Wrap (Alt+Z)">
            <i class="bi bi-text-wrap"></i>
          </button>

          <!-- Font Size Adjusters -->
          <button id="fontSizeDecBtn" class="studio-editor-tool" title="Decrease Font Size">
            <i class="bi bi-dash"></i>
          </button>
          <span id="fontSizeDisplay" class="small font-mono text-muted px-1" style="font-size: 0.75rem;">13px</span>
          <button id="fontSizeIncBtn" class="studio-editor-tool" title="Increase Font Size">
            <i class="bi bi-plus"></i>
          </button>

          <span class="text-secondary opacity-50 mx-1">|</span>

          <!-- Delete Active File Button -->
          @if($activeFile)
            <button id="deleteFileBtn" class="studio-editor-tool text-danger" title="Delete Active File" onclick="deleteActiveFile()">
              <i class="bi bi-trash"></i>
            </button>
          @endif

          <!-- Expand Preview if Preview is collapsed -->
          <button id="expandPreviewBtn" class="studio-editor-tool d-none" title="Expand Preview">
            <i class="bi bi-layout-sidebar-reverse"></i>
          </button>
        </div>
      </div>

      <!-- Editor Canvas -->
      <div class="studio-editor-canvas" id="editorCanvas">
        <textarea id="codeEditor" class="editor-textarea-fallback" spellcheck="false">{{ $activeContent }}</textarea>
      </div>

      <!-- Editor Status Bar -->
      <footer class="studio-statusbar">
        <div class="d-flex align-items-center gap-3">
          <span id="cursorPos">Ln 1, Col 1</span>
          <span id="charCount">0 chars</span>
          <span id="saveStatusMini" class="d-none d-sm-inline">Ready</span>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span id="langMode">HTML</span>
          <span class="d-none d-sm-inline">UTF-8</span>
          <span>Spaces: 2</span>
        </div>
      </footer>
    </main>

    <!-- Resizer 2 (Center Editor <-> Right Preview) -->
    <div class="studio-resizer" id="previewResizer" title="Drag to resize preview panel (double-click to reset)"></div>

    <!-- Panel 3: Right Panel (Live Preview & MCP Activity) -->
    <aside class="studio-preview-panel" id="studioPreviewPanel">
      <!-- Right Panel Tab Header -->
      <div class="studio-preview-header">
        <!-- View Tabs: Preview / Activity -->
        <ul class="nav nav-pills gap-1" role="tablist">
          <li class="nav-item">
            <button class="studio-btn studio-btn-ghost active py-0 px-2" data-bs-toggle="tab" data-bs-target="#tabPreview" id="previewTabBtn">
              <i class="bi bi-play-circle me-1"></i> Preview
            </button>
          </li>
          <li class="nav-item">
            <button class="studio-btn studio-btn-ghost py-0 px-2" data-bs-toggle="tab" data-bs-target="#tabAudit" id="auditTabBtn">
              <i class="bi bi-cpu me-1"></i> Activity ({{ $auditLogs->count() }})
            </button>
          </li>
        </ul>

        <!-- Right Side Preview Controls -->
        <div class="d-flex align-items-center gap-1">
          <!-- Device Viewport Switcher -->
          <div class="btn-group btn-group-sm" role="group" id="viewportControls">
            <button type="button" class="studio-editor-tool active" onclick="setViewport('100%')" title="Desktop View (100%)">
              <i class="bi bi-display"></i>
            </button>
            <button type="button" class="studio-editor-tool" onclick="setViewport('768px')" title="Tablet View (768px)">
              <i class="bi bi-tablet"></i>
            </button>
            <button type="button" class="studio-editor-tool" onclick="setViewport('375px')" title="Mobile View (375px)">
              <i class="bi bi-phone"></i>
            </button>
          </div>

          <span class="text-secondary opacity-50 mx-1">|</span>

          <!-- Reload Preview -->
          <button class="studio-editor-tool" id="refreshPreviewBtn" onclick="refreshPreview()" title="Reload Preview">
            <i class="bi bi-arrow-clockwise"></i>
          </button>

          <!-- Fullscreen Toggle -->
          <button class="studio-editor-tool" id="fullscreenPreviewBtn" onclick="toggleFullscreenPreview()" title="Toggle Fullscreen Preview">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>

          <!-- Collapse Preview Toggle -->
          <button id="collapsePreviewBtn" class="studio-editor-tool d-none d-lg-inline-flex" title="Collapse Preview">
            <i class="bi bi-layout-sidebar-reverse"></i>
          </button>
        </div>
      </div>

      <!-- Tab Content Area -->
      <div class="tab-content flex-grow-1 d-flex flex-column" style="overflow: hidden;">
        <!-- Tab 1: Live Preview Stage -->
        <div class="tab-pane fade show active flex-grow-1 d-flex flex-column h-100" id="tabPreview">
          <div class="studio-preview-stage" id="previewStage">
            <iframe id="studioPreviewIframe" 
                    src="{{ route('preview.show', $project->slug) }}" 
                    class="h-100" 
                    style="width: 100%;"
                    sandbox="allow-scripts allow-forms allow-modals allow-downloads"></iframe>
          </div>
        </div>

        <!-- Tab 2: MCP Activity Log -->
        <div class="tab-pane fade flex-grow-1 p-3 font-mono small h-100" id="tabAudit" style="overflow-y: auto;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-body font-sans" style="font-size: 0.85rem;">
              <i class="bi bi-cpu-fill text-info me-1"></i> MCP Activity & Audit Log
            </h6>
            <span class="badge bg-body-tertiary border text-secondary font-mono small">{{ $auditLogs->count() }} events</span>
          </div>

          @forelse($auditLogs as $log)
            <div class="p-2 mb-2 rounded-2 border border-subtle bg-body-tertiary">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge font-mono" style="background: var(--violet-soft); color: var(--violet-500); font-size: 0.72rem;">
                  {{ $log->action }}
                </span>
                <span class="text-secondary" style="font-size: 0.7rem;">{{ $log->created_at->format('M d H:i:s') }}</span>
              </div>
              @if($log->payload)
                <pre class="mb-0 text-secondary small p-1 rounded bg-black-50 overflow-x-auto font-mono" style="font-size: 0.7rem; max-height: 80px;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
              @endif
            </div>
          @empty
            <div class="text-center py-5">
              <i class="bi bi-robot fs-2 text-secondary d-block mb-2"></i>
              <p class="text-muted small mb-0">No MCP tool invocations recorded yet.</p>
              <span class="text-secondary small">Instruct ChatGPT via Model Context Protocol to see actions live.</span>
            </div>
          @endforelse
        </div>
      </div>
    </aside>

  </div>
</div>

<!-- Modal 1: Create New File -->
<div class="modal fade" id="newFileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bento-card border-0 p-0 overflow-hidden shadow-lg">
      <div class="modal-header border-bottom py-3 px-4" style="border-color: var(--border-subtle) !important;">
        <h5 class="modal-title h6 fw-bold mb-0 text-body">
          <i class="bi bi-file-earmark-plus me-1 text-info"></i> Create New File
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <label class="form-label small fw-semibold text-body">Relative File Path</label>
        <input type="text" id="newFilePathInput" class="form-control font-mono shadow-none" placeholder="e.g. helpers.js, style.css, modal.html">
        <div class="form-text small mt-2">
          Supported extensions: <code>.html</code>, <code>.css</code>, <code>.js</code>, <code>.json</code>, <code>.svg</code>, <code>.md</code>, <code>.txt</code>
        </div>
      </div>
      <div class="modal-footer border-top py-2 px-4" style="border-color: var(--border-subtle) !important;">
        <button type="button" class="studio-btn studio-btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="studio-btn studio-btn-primary" onclick="createNewFile()">Create File</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal 2: Create Snapshot -->
<div class="modal fade" id="snapshotModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bento-card border-0 p-0 overflow-hidden shadow-lg">
      <div class="modal-header border-bottom py-3 px-4" style="border-color: var(--border-subtle) !important;">
        <h5 class="modal-title h6 fw-bold mb-0 text-body">
          <i class="bi bi-camera me-1 text-info"></i> Create Version Snapshot
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
          <label class="form-label small fw-semibold text-body">Version Tag</label>
          <input type="text" id="snapTagInput" class="form-control font-mono shadow-none" value="v1.0.{{ $project->snapshots->count() + 1 }}">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold text-body">Snapshot Description / Label</label>
          <input type="text" id="snapLabelInput" class="form-control shadow-none" placeholder="e.g. Added responsive layout & copy button">
        </div>
      </div>
      <div class="modal-footer border-top py-2 px-4" style="border-color: var(--border-subtle) !important;">
        <button type="button" class="studio-btn studio-btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="studio-btn studio-btn-primary" onclick="createSnapshot()">Save Snapshot</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- CodeMirror Core and Syntax Modes -->
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/markdown/markdown.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closetag.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/selection/active-line.min.js"></script>

<script>
(function() {
  let currentFile = '{{ $activeFile->path ?? "" }}';
  let isDirty = false;
  let cmEditor = null;
  let currentFontSize = 13;
  let isWordWrap = false;
  const projectUuid = '{{ $project->uuid }}';

  const rawTextarea = document.getElementById('codeEditor');
  const unsavedBadge = document.getElementById('unsavedBadge');
  const tabDirtyDot = document.getElementById('tabDirtyDot');
  const saveStatus = document.getElementById('saveStatus');
  const saveStatusMini = document.getElementById('saveStatusMini');
  const cursorPosEl = document.getElementById('cursorPos');
  const charCountEl = document.getElementById('charCount');
  const langModeEl = document.getElementById('langMode');
  const fontSizeDisplay = document.getElementById('fontSizeDisplay');

  // Detect mode based on filename
  function getModeForPath(path) {
    const ext = path.split('.').pop().toLowerCase();
    switch (ext) {
      case 'html':
      case 'htm':
        return 'htmlmixed';
      case 'js':
        return 'javascript';
      case 'json':
        return { name: 'javascript', json: true };
      case 'css':
        return 'css';
      case 'md':
        return 'markdown';
      default:
        return 'text/plain';
    }
  }

  function getLangLabel(path) {
    const ext = path.split('.').pop().toLowerCase();
    switch (ext) {
      case 'html': return 'HTML (Mixed)';
      case 'js': return 'JavaScript';
      case 'json': return 'JSON';
      case 'css': return 'CSS';
      case 'md': return 'Markdown';
      default: return 'Text';
    }
  }

  // Initialize CodeMirror 5 with Fallback
  try {
    if (typeof CodeMirror !== 'undefined') {
      cmEditor = CodeMirror.fromTextArea(rawTextarea, {
        lineNumbers: true,
        mode: getModeForPath(currentFile),
        theme: 'default',
        tabSize: 2,
        indentUnit: 2,
        indentWithTabs: false,
        lineWrapping: false,
        autoCloseTags: true,
        autoCloseBrackets: true,
        styleActiveLine: true,
        viewportMargin: 50,
      });

      // Update Cursor Position & Stats
      cmEditor.on('cursorActivity', () => {
        const pos = cmEditor.getCursor();
        cursorPosEl.textContent = `Ln ${pos.line + 1}, Col ${pos.ch + 1}`;
      });

      // Detect Changes
      cmEditor.on('change', () => {
        triggerDirty();
        charCountEl.textContent = `${cmEditor.getValue().length} chars`;
      });

      // Hotkey: Ctrl+S / Cmd+S
      cmEditor.setOption('extraKeys', {
        'Ctrl-S': function() { saveCurrentFile(); },
        'Cmd-S': function() { saveCurrentFile(); },
        'Tab': function(cm) {
          if (cm.somethingSelected()) {
            cm.indentSelection('add');
          } else {
            cm.replaceSelection('  ', 'end');
          }
        }
      });

      // Initial stats
      charCountEl.textContent = `${cmEditor.getValue().length} chars`;
      langModeEl.textContent = getLangLabel(currentFile);
    }
  } catch (e) {
    console.warn('CodeMirror failed to initialize, using enhanced textarea fallback:', e);
  }

  // Fallback textarea event listeners
  if (!cmEditor && rawTextarea) {
    rawTextarea.addEventListener('keydown', (e) => {
      if (e.key === 'Tab') {
        e.preventDefault();
        const start = rawTextarea.selectionStart;
        const end = rawTextarea.selectionEnd;
        rawTextarea.value = rawTextarea.value.substring(0, start) + "  " + rawTextarea.value.substring(end);
        rawTextarea.selectionStart = rawTextarea.selectionEnd = start + 2;
        triggerDirty();
      }
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveCurrentFile();
      }
    });
    rawTextarea.addEventListener('input', () => {
      triggerDirty();
      charCountEl.textContent = `${rawTextarea.value.length} chars`;
    });
  }

  // Global window keydown for Ctrl+S
  window.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault();
      saveCurrentFile();
    }
  });

  function getEditorContent() {
    return cmEditor ? cmEditor.getValue() : rawTextarea.value;
  }

  function setEditorContent(content) {
    if (cmEditor) {
      cmEditor.setValue(content);
      cmEditor.clearHistory();
    } else {
      rawTextarea.value = content;
    }
    charCountEl.textContent = `${content.length} chars`;
  }

  function triggerDirty() {
    if (!isDirty) {
      isDirty = true;
      unsavedBadge.classList.remove('d-none');
      tabDirtyDot.classList.remove('d-none');
      saveStatus.textContent = 'Unsaved changes';
      saveStatusMini.textContent = 'Unsaved';
    }
  }

  function markSaved() {
    isDirty = false;
    unsavedBadge.classList.add('d-none');
    tabDirtyDot.classList.add('d-none');
    const timeStr = new Date().toLocaleTimeString();
    saveStatus.textContent = 'Saved at ' + timeStr;
    saveStatusMini.textContent = 'Saved ' + timeStr;
  }

  // Save current file via AJAX
  function saveCurrentFile() {
    if (!currentFile) return;

    saveStatus.textContent = 'Saving…';
    saveStatusMini.textContent = 'Saving…';

    fetch(`/studio/${projectUuid}/file`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({
        path: currentFile,
        content: getEditorContent()
      })
    })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(data => {
      markSaved();
      refreshPreview();
    })
    .catch(err => {
      saveStatus.textContent = 'Error saving!';
      saveStatusMini.textContent = 'Save error';
      alert('Failed to save file: ' + err.message);
    });
  }
  document.getElementById('saveBtn').addEventListener('click', saveCurrentFile);

  // File Switching
  window.selectFile = function(path) {
    if (isDirty) {
      if (!confirm('You have unsaved changes in ' + currentFile + '. Switch anyway?')) return;
    }

    currentFile = path;
    document.getElementById('activeFileName').textContent = path;
    langModeEl.textContent = getLangLabel(path);

    // Update active highlight in sidebar
    document.querySelectorAll('.file-tree-item').forEach(el => {
      el.classList.toggle('active', el.getAttribute('data-path') === path);
    });

    saveStatus.textContent = 'Loading…';
    fetch(`/studio/${projectUuid}/file?path=${encodeURIComponent(path)}`)
      .then(r => r.json())
      .then(data => {
        setEditorContent(data.content);
        if (cmEditor) {
          cmEditor.setOption('mode', getModeForPath(path));
        }
        isDirty = false;
        unsavedBadge.classList.add('d-none');
        tabDirtyDot.classList.add('d-none');
        saveStatus.textContent = 'Ready';
        saveStatusMini.textContent = 'Ready';

        // Auto-switch to editor view on mobile
        if (window.innerWidth < 768) {
          switchMobileTab('editor');
        }
      })
      .catch(err => {
        saveStatus.textContent = 'Load error';
      });
  };

  // Preview Reload
  window.refreshPreview = function() {
    const iframe = document.getElementById('studioPreviewIframe');
    const refreshBtn = document.getElementById('refreshPreviewBtn');
    if (refreshBtn) {
      const icon = refreshBtn.querySelector('i');
      if (icon) icon.classList.add('spin-icon');
      setTimeout(() => { if (icon) icon.classList.remove('spin-icon'); }, 600);
    }
    iframe.src = iframe.src;
  };

  // Viewport Switcher
  window.setViewport = function(width) {
    const iframe = document.getElementById('studioPreviewIframe');
    iframe.style.width = width;
    document.querySelectorAll('#viewportControls button').forEach(btn => btn.classList.remove('active'));
    if (event && event.currentTarget) {
      event.currentTarget.classList.add('active');
    }
  };

  // Toggle Fullscreen Preview
  window.toggleFullscreenPreview = function() {
    const previewPanel = document.getElementById('studioPreviewPanel');
    const btn = document.getElementById('fullscreenPreviewBtn');
    previewPanel.classList.toggle('is-fullscreen');
    if (previewPanel.classList.contains('is-fullscreen')) {
      btn.innerHTML = '<i class="bi bi-fullscreen-exit"></i>';
      btn.title = 'Exit Fullscreen';
    } else {
      btn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
      btn.title = 'Toggle Fullscreen Preview';
    }
  };

  // Word Wrap Toggle
  const toggleWrapBtn = document.getElementById('toggleWrapBtn');
  if (toggleWrapBtn) {
    toggleWrapBtn.addEventListener('click', () => {
      isWordWrap = !isWordWrap;
      toggleWrapBtn.classList.toggle('active', isWordWrap);
      if (cmEditor) {
        cmEditor.setOption('lineWrapping', isWordWrap);
      } else if (rawTextarea) {
        rawTextarea.style.whiteSpace = isWordWrap ? 'pre-wrap' : 'pre';
      }
    });
  }

  // Font Size Adjustments
  const fontSizeIncBtn = document.getElementById('fontSizeIncBtn');
  const fontSizeDecBtn = document.getElementById('fontSizeDecBtn');
  function setFontSize(newSize) {
    currentFontSize = Math.min(22, Math.max(10, newSize));
    fontSizeDisplay.textContent = currentFontSize + 'px';
    const cmEl = document.querySelector('.CodeMirror');
    if (cmEl) {
      cmEl.style.fontSize = currentFontSize + 'px';
      if (cmEditor) cmEditor.refresh();
    }
    if (rawTextarea) {
      rawTextarea.style.fontSize = currentFontSize + 'px';
    }
  }
  if (fontSizeIncBtn) fontSizeIncBtn.addEventListener('click', () => setFontSize(currentFontSize + 1));
  if (fontSizeDecBtn) fontSizeDecBtn.addEventListener('click', () => setFontSize(currentFontSize - 1));

  // Collapsible Sidebar & Preview
  const sidebar = document.getElementById('studioSidebar');
  const previewPanel = document.getElementById('studioPreviewPanel');
  const collapseSidebarBtn = document.getElementById('collapseSidebarBtn');
  const expandSidebarBtn = document.getElementById('expandSidebarBtn');
  const collapsePreviewBtn = document.getElementById('collapsePreviewBtn');
  const expandPreviewBtn = document.getElementById('expandPreviewBtn');

  function toggleSidebar(collapsed) {
    sidebar.classList.toggle('collapsed', collapsed);
    expandSidebarBtn.classList.toggle('d-none', !collapsed);
    if (cmEditor) setTimeout(() => cmEditor.refresh(), 150);
  }

  function togglePreview(collapsed) {
    previewPanel.classList.toggle('collapsed', collapsed);
    expandPreviewBtn.classList.toggle('d-none', !collapsed);
    if (cmEditor) setTimeout(() => cmEditor.refresh(), 150);
  }

  if (collapseSidebarBtn) collapseSidebarBtn.addEventListener('click', () => toggleSidebar(true));
  if (expandSidebarBtn) expandSidebarBtn.addEventListener('click', () => toggleSidebar(false));
  if (collapsePreviewBtn) collapsePreviewBtn.addEventListener('click', () => togglePreview(true));
  if (expandPreviewBtn) expandPreviewBtn.addEventListener('click', () => togglePreview(false));

  // Draggable Splitters
  function setupResizer(resizerEl, targetEl, isLeft, minW, maxW, defaultW) {
    if (!resizerEl || !targetEl) return;
    let isDragging = false;

    resizerEl.addEventListener('mousedown', (e) => {
      e.preventDefault();
      isDragging = true;
      resizerEl.classList.add('is-dragging');
      document.body.style.cursor = 'col-resize';
      document.body.style.userSelect = 'none';

      const iframe = document.getElementById('studioPreviewIframe');
      if (iframe) iframe.style.pointerEvents = 'none';

      function onMouseMove(ev) {
        if (!isDragging) return;
        let newWidth;
        if (isLeft) {
          newWidth = ev.clientX - targetEl.getBoundingClientRect().left;
        } else {
          newWidth = targetEl.getBoundingClientRect().right - ev.clientX;
        }
        if (newWidth >= minW && newWidth <= maxW) {
          targetEl.style.width = newWidth + 'px';
          if (cmEditor) cmEditor.refresh();
        }
      }

      function onMouseUp() {
        isDragging = false;
        resizerEl.classList.remove('is-dragging');
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        if (iframe) iframe.style.pointerEvents = 'auto';
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
        if (cmEditor) cmEditor.refresh();
      }

      window.addEventListener('mousemove', onMouseMove);
      window.addEventListener('mouseup', onMouseUp);
    });

    // Double-click to reset default width
    resizerEl.addEventListener('dblclick', () => {
      targetEl.style.width = defaultW + 'px';
      if (cmEditor) cmEditor.refresh();
    });
  }

  setupResizer(document.getElementById('sidebarResizer'), sidebar, true, 170, 420, 230);
  setupResizer(document.getElementById('previewResizer'), previewPanel, false, 280, 780, 450);

  // Mobile View Switcher (<768px)
  window.switchMobileTab = function(tabName) {
    document.querySelectorAll('.studio-mobile-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.tab === tabName);
    });

    sidebar.classList.remove('mobile-active');
    document.getElementById('studioEditorPanel').classList.remove('mobile-active');
    previewPanel.classList.remove('mobile-active');

    if (tabName === 'files') {
      sidebar.classList.add('mobile-active');
    } else if (tabName === 'editor') {
      document.getElementById('studioEditorPanel').classList.add('mobile-active');
      if (cmEditor) cmEditor.refresh();
    } else if (tabName === 'preview') {
      previewPanel.classList.add('mobile-active');
      const pTab = document.getElementById('previewTabBtn');
      if (pTab) bootstrap.Tab.getOrCreateInstance(pTab).show();
    } else if (tabName === 'mcp') {
      previewPanel.classList.add('mobile-active');
      const aTab = document.getElementById('auditTabBtn');
      if (aTab) bootstrap.Tab.getOrCreateInstance(aTab).show();
    }
  };

  // Create New File
  window.createNewFile = function() {
    const input = document.getElementById('newFilePathInput');
    const path = input.value.trim();
    if (!path) return;

    fetch(`/studio/${projectUuid}/file/create`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ path: path, content: '' })
    })
    .then(r => {
      if (!r.ok) throw new Error('File already exists or invalid path');
      return r.json();
    })
    .then(() => {
      location.reload();
    })
    .catch(err => alert(err.message));
  };

  // Delete Active File
  window.deleteActiveFile = function() {
    if (!currentFile) return;
    if (!confirm(`Delete "${currentFile}" permanently? This action cannot be undone.`)) return;

    fetch(`/studio/${projectUuid}/file`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ path: currentFile })
    })
    .then(r => r.json())
    .then(() => location.reload())
    .catch(err => alert('Failed to delete file: ' + err.message));
  };

  // Create Snapshot
  window.createSnapshot = function() {
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
    .then(() => location.reload())
    .catch(err => alert('Failed to create snapshot: ' + err.message));
  };

})();
</script>
@endpush
