@extends('layouts.app')

@section('title', 'MicroTool Hub — Discover, Build & Run Web Micro-Tools')

@section('content')
<!-- Hero Section -->
<section class="hero-gradient py-5 border-bottom">
  <div class="container py-4 text-center">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-4 rounded-pill bg-body-tertiary border shadow-sm">
      <span class="badge bg-info text-dark font-mono">NEW</span>
      <span class="small text-muted">Model Context Protocol (MCP) Streamable HTTP & In-Chat Previews</span>
    </div>

    <h1 class="display-4 fw-extrabold tracking-tight mb-3">
      The Open Community for <br class="d-none d-md-block">
      <span class="text-transparent bg-clip-text" style="background: var(--mcp-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Single-Purpose Web Tools</span>
    </h1>

    <p class="lead text-muted mx-auto mb-4" style="max-width: 680px;">
      Discover, create, edit, preview, and fork responsive calculators, formatters, generators, and visual widgets. Code manually in our browser Studio or orchestrate everything through ChatGPT via MCP.
    </p>

    <!-- Hero Search -->
    <div class="mx-auto mb-4" style="max-width: 600px;">
      <form action="{{ route('explore') }}" method="GET" class="card p-2 border shadow-lg rounded-pill bg-body">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-0 ps-3"><i class="bi bi-search text-info fs-5"></i></span>
          <input type="text" name="q" class="form-control bg-transparent border-0 shadow-none ps-2" placeholder="Search calculators, formatters, converters...">
          <button class="btn btn-gradient rounded-pill px-4" type="submit">Explore</button>
        </div>
      </form>
    </div>

    <!-- Quick Starter Prompts for ChatGPT -->
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 small text-muted">
      <span class="me-1"><i class="bi bi-chat-quote-fill text-info"></i> Starter Prompts:</span>
      <a href="{{ route('explore', ['q' => 'JSON']) }}" class="badge bg-body-tertiary border text-decoration-none text-muted py-2 px-3 rounded-pill">JSON Formatter</a>
      <a href="{{ route('explore', ['q' => 'Box-Shadow']) }}" class="badge bg-body-tertiary border text-decoration-none text-muted py-2 px-3 rounded-pill">CSS Shadow Generator</a>
      <a href="{{ route('explore', ['q' => 'Timestamp']) }}" class="badge bg-body-tertiary border text-decoration-none text-muted py-2 px-3 rounded-pill">Timestamp Converter</a>
      <a href="{{ route('dashboard.index') }}" class="badge bg-info-subtle border border-info text-info text-decoration-none py-2 px-3 rounded-pill fw-semibold">+ Build Custom Tool</a>
    </div>

    <!-- Metrics -->
    <div class="row g-4 mt-4 pt-3 justify-content-center text-center">
      <div class="col-4 col-md-3">
        <div class="fs-3 fw-bold text-info">{{ $stats['total_tools'] }}+</div>
        <div class="text-muted small">Published Tools</div>
      </div>
      <div class="col-4 col-md-3">
        <div class="fs-3 fw-bold text-primary">{{ $stats['total_creators'] }}</div>
        <div class="text-muted small">Community Creators</div>
      </div>
      <div class="col-4 col-md-3">
        <div class="fs-3 fw-bold text-success">{{ $stats['total_forks'] }}</div>
        <div class="text-muted small">Project Forks</div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Tools Section -->
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <h2 class="fw-bold mb-1">Featured Community Tools</h2>
        <p class="text-muted small mb-0">Staff-picked micro-tools ready to use, fork, or embed.</p>
      </div>
      <a href="{{ route('explore') }}" class="btn btn-sm btn-outline-info">View All Tools <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-4">
      @forelse($featuredTools as $tool)
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 tool-card">
            <div class="card-body d-flex flex-direction-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-body-tertiary border text-info small">
                  <i class="bi {{ $tool->category?->icon ?? 'bi-tools' }} me-1"></i> {{ $tool->category?->name ?? 'Utility' }}
                </span>
                <span class="badge bg-secondary-subtle text-secondary font-mono">{{ $tool->current_version }}</span>
              </div>

              <h5 class="card-title fw-bold mb-2">
                <a href="{{ route('tools.show', $tool->slug) }}" class="text-decoration-none text-body">
                  {{ $tool->name }}
                </a>
              </h5>

              <p class="card-text text-muted small flex-grow-1">
                {{ Str::limit($tool->description, 110) }}
              </p>

              <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-3 small text-muted">
                <div class="d-flex align-items-center gap-1">
                  <i class="bi bi-person-circle"></i>
                  <span>{{ $tool->user->name }}</span>
                </div>
                <div class="d-flex gap-3">
                  <span><i class="bi bi-heart-fill text-danger me-1"></i>{{ $tool->likes_count }}</span>
                  <span><i class="bi bi-git me-1"></i>{{ $tool->forks_count }}</span>
                </div>
              </div>

              <div class="mt-3 d-grid gap-2 d-flex">
                <a href="{{ route('preview.show', $tool->slug) }}" target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1">
                  <i class="bi bi-play-circle me-1"></i> Launch Tool
                </a>
                <a href="{{ route('tools.show', $tool->slug) }}" class="btn btn-sm btn-body border">
                  Details
                </a>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12 text-center py-5">
          <p class="text-muted">No featured tools published yet. Be the first creator!</p>
          <a href="{{ route('dashboard.index') }}" class="btn btn-sm btn-gradient">Create Micro-Tool</a>
        </div>
      @endforelse
    </div>
  </div>
</section>

<!-- Popular Categories Grid -->
<section class="py-5 bg-body-tertiary border-top border-bottom" id="categories">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold mb-1">Browse by Category</h2>
      <p class="text-muted small">Find the right micro-tool for your specific workflow.</p>
    </div>

    <div class="row g-3">
      @foreach($categories as $category)
        <div class="col-6 col-md-4 col-lg-2">
          <a href="{{ route('explore', ['category' => $category->slug]) }}" class="card text-center h-100 p-3 tool-card text-decoration-none text-body">
            <div class="fs-2 text-info mb-2"><i class="bi {{ $category->icon }}"></i></div>
            <h6 class="fw-bold mb-1">{{ $category->name }}</h6>
            <div class="text-muted small">{{ $category->projects_count }} tools</div>
          </a>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- How ChatGPT MCP Works -->
<section class="py-5" id="chatgpt-guide">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="badge badge-mcp mb-3">Model Context Protocol 1.0</span>
        <h2 class="fw-bold mb-3">Build Micro-Tools Inside ChatGPT</h2>
        <p class="text-muted">
          MicroTool Hub implements a full Streamable HTTP MCP server compatible with OpenAI ChatGPT and Codex. AI assistants can create workspaces, write and modify code, generate snapshots, and render interactive in-chat widgets.
        </p>

        <div class="d-flex flex-column gap-3 mt-4">
          <div class="d-flex gap-3">
            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
              <strong>1</strong>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Describe Your Tool to ChatGPT</h6>
              <p class="text-muted small mb-0">Ask ChatGPT: <em>"Create an interactive RGB to HEX color converter with copy buttons."</em></p>
            </div>
          </div>

          <div class="d-flex gap-3">
            <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
              <strong>2</strong>
            </div>
            <div>
              <h6 class="fw-bold mb-1">ChatGPT Writes Code via MCP</h6>
              <p class="text-muted small mb-0">ChatGPT invokes <code>create_project</code> and <code>write_project_file</code> to save atomic HTML, CSS, and JS into your private sandbox.</p>
            </div>
          </div>

          <div class="d-flex gap-3">
            <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
              <strong>3</strong>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Live In-Chat Preview & Publishing</h6>
              <p class="text-muted small mb-0">ChatGPT calls <code>render_microtool_widget</code> to mount the interactive tool directly in chat, then publishes it with <code>publish_project</code>.</p>
            </div>
          </div>
        </div>

        <div class="mt-4 pt-2">
          <a href="{{ route('dashboard.tokens') }}" class="btn btn-gradient me-2">Connect ChatGPT Now</a>
          <a href="{{ url('/.well-known/oauth-protected-resource') }}" target="_blank" class="btn btn-outline-secondary">View RFC 9728 Discovery</a>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card bg-dark text-white border-secondary p-3 shadow-lg rounded-4 font-mono small">
          <div class="d-flex align-items-center gap-2 pb-2 border-bottom border-secondary mb-3">
            <span class="badge bg-danger rounded-circle p-1"></span>
            <span class="badge bg-warning rounded-circle p-1"></span>
            <span class="badge bg-success rounded-circle p-1"></span>
            <span class="text-muted ms-2">chatgpt-streamable-http.json</span>
          </div>
          <pre class="mb-0 text-info"><code>// ChatGPT Tool Call: render_microtool_widget
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "render_microtool_widget",
    "arguments": {
      "project_id": "json-formatter"
    }
  }
}

// Server Response:
{
  "result": {
    "structuredContent": { ... },
    "_meta": {
      "ui": {
        "resourceUri": "ui://microtool/preview.html"
      }
    }
  }
}</code></pre>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Recently Published Tools -->
<section class="py-5">
  <div class="container">
    <h2 class="fw-bold mb-4">Recently Added Tools</h2>
    <div class="row g-3">
      @foreach($recentTools as $tool)
        <div class="col-md-6 col-lg-3">
          <div class="card h-100 tool-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge bg-body-tertiary border text-info small">{{ $tool->category?->name ?? 'Utility' }}</span>
              <span class="text-muted small">{{ $tool->published_at?->diffForHumans() }}</span>
            </div>
            <h6 class="fw-bold mb-1">
              <a href="{{ route('tools.show', $tool->slug) }}" class="text-decoration-none text-body">
                {{ $tool->name }}
              </a>
            </h6>
            <p class="text-muted small flex-grow-1 mb-2">{{ Str::limit($tool->description, 75) }}</p>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
              <span>{{ $tool->user->name }}</span>
              <span><i class="bi bi-heart text-danger"></i> {{ $tool->likes_count }}</span>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endsection
