@extends('layouts.app')

@section('title', 'MicroTool Hub — Small Tools. Big Productivity.')
@section('meta_description', 'Free online tools to convert, edit, calculate, and create — all in one place. Powered by Laravel and ChatGPT MCP.')

@section('content')
<!-- Hero Section (Dark Navy & Electric Violet Glow) -->
<section class="hero-navy">
  <div class="container text-center">
    <!-- Eyebrow Badge -->
    <div class="d-inline-flex align-items-center">
      <span class="hero-tag">
        <i class="bi bi-stars"></i>
        <span>CLIENT-SIDE ISOLATED &bull; CHATGPT MCP READY</span>
      </span>
    </div>

    <!-- Main Headline -->
    <h1 class="hero-title">
      Small Tools. <br class="d-none d-sm-block">
      <span class="hero-gradient-text">Big Productivity.</span>
    </h1>

    <!-- Supporting Text -->
    <p class="hero-subtext">
      Free online tools to convert, edit, calculate, and create — all in one place.
      Processed locally in your browser with zero server uploads and total privacy.
    </p>

    <!-- Large Prominent Tool Search -->
    <div class="hero-search-wrapper">
      <form action="{{ route('explore') }}" method="GET" class="hero-search-box">
        <i class="bi bi-search text-secondary fs-5 me-2"></i>
        <input 
          type="search" 
          name="q" 
          class="hero-search-input" 
          placeholder="Search formatters, converters, calculators, image tools..." 
          aria-label="Search tools"
          autocomplete="off"
        >
        <button class="btn-violet" type="submit">
          <span>Search</span>
          <i class="bi bi-arrow-right"></i>
        </button>
      </form>
    </div>

    <!-- Popular Search Suggestions -->
    <div class="search-chips">
      <span class="text-secondary small me-1"><i class="bi bi-lightning-charge-fill" style="color: var(--violet-500);"></i> Popular:</span>
      <a href="{{ route('explore', ['q' => 'JPG to PNG']) }}" class="search-chip">JPG to PNG</a>
      <a href="{{ route('explore', ['q' => 'Image Resizer']) }}" class="search-chip">Image Resizer</a>
      <a href="{{ route('explore', ['q' => 'JSON Formatter']) }}" class="search-chip">JSON Formatter</a>
      <a href="{{ route('explore', ['q' => 'Box-Shadow']) }}" class="search-chip">CSS Box-Shadow</a>
      <a href="{{ route('explore', ['category' => 'converters']) }}" class="search-chip">Converters</a>
      <a href="{{ route('explore', ['category' => 'calculators']) }}" class="search-chip">Calculators</a>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 justify-content-center mt-4 pt-3">
      <div class="col-4 col-md-3">
        <div class="fs-4 fw-bold" style="color: var(--violet-500);">{{ max(1, $stats['total_tools']) }}+</div>
        <div class="text-secondary small">Published Tools</div>
      </div>
      <div class="col-4 col-md-3">
        <div class="fs-4 fw-bold text-body">{{ max(1, $stats['total_creators']) }}</div>
        <div class="text-secondary small">Tool Creators</div>
      </div>
      <div class="col-4 col-md-3">
        <div class="fs-4 fw-bold" style="color: var(--color-success);">100%</div>
        <div class="text-secondary small">Browser Private</div>
      </div>
    </div>
  </div>
</section>

<!-- Category Navigation -->
<section id="categories" class="py-5 border-bottom" style="background: var(--bg-surface); border-color: var(--border-subtle) !important;">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <h2 class="h4 fw-bold mb-1">Browse by Category</h2>
        <p class="text-secondary small mb-0">Jump directly into specialized single-purpose tool sets.</p>
      </div>
      <a href="{{ route('explore') }}" class="btn-outline-violet small text-decoration-none py-1 px-3 d-none d-sm-inline-flex">
        All Categories <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <!-- Category Chips Carousel / Row -->
    <div class="category-chips-row">
      <a href="{{ route('explore') }}" class="category-chip-btn {{ !request('category') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>All Tools</span>
      </a>
      @foreach($categories as $category)
        <a href="{{ route('explore', ['category' => $category->slug]) }}" class="category-chip-btn">
          <i class="bi {{ $category->icon ?? 'bi-tools' }}"></i>
          <span>{{ $category->name }}</span>
          @if($category->projects_count > 0)
            <span class="badge rounded-pill bg-body-tertiary text-secondary font-mono small ms-1">{{ $category->projects_count }}</span>
          @endif
        </a>
      @endforeach
    </div>
  </div>
</section>

<!-- Featured Tools (Responsive Bento Grid) -->
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <div class="d-inline-flex align-items-center gap-2 mb-2">
          <span class="badge font-mono small py-1 px-2" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
            CURATED CATALOG
          </span>
        </div>
        <h2 class="h3 fw-bold mb-1">Featured Micro-Tools</h2>
        <p class="text-secondary small mb-0">High-performance tools running directly in your web browser.</p>
      </div>
      <a href="{{ route('explore') }}" class="btn-outline-violet text-decoration-none py-1 px-3 small">
        Explore Directory <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <!-- Bento Grid Container -->
    <div class="bento-grid">
      @php
        $toolsToDisplay = $featuredTools->isNotEmpty() ? $featuredTools : $recentTools;
      @endphp

      @forelse($toolsToDisplay as $index => $tool)
        @php
          // Assign varied responsive Bento column spans:
          // First tool is high-impact 8-span if on desktop, others 4-span
          $isPrimary = ($index === 0 && count($toolsToDisplay) > 2);
          $colClass = $isPrimary ? 'col-bento-8 bento-featured' : 'col-bento-4';
        @endphp

        <div class="{{ $colClass }}">
          <div class="bento-card h-100">
            <!-- Top Badges -->
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="d-flex align-items-center gap-2">
                <div class="bento-icon-box mb-0">
                  <i class="bi {{ $tool->category?->icon ?? 'bi-cpu' }}"></i>
                </div>
                <div>
                  <span class="badge py-1 px-2 font-mono small" style="background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-subtle);">
                    {{ $tool->category?->name ?? 'Utility' }}
                  </span>
                  @if($tool->is_featured)
                    <span class="badge py-1 px-2 font-mono small ms-1" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
                      ★ Featured
                    </span>
                  @endif
                </div>
              </div>
              <span class="badge font-mono small text-secondary bg-body-tertiary border">
                {{ $tool->current_version }}
              </span>
            </div>

            <!-- Title & Description -->
            <h3 class="bento-card-title">
              <a href="{{ route('tools.show', $tool->slug) }}">
                {{ $tool->name }}
              </a>
            </h3>

            <p class="bento-card-desc">
              {{ Str::limit($tool->description, $isPrimary ? 220 : 130) }}
            </p>

            <!-- Card Actions -->
            <div class="d-flex gap-2 mb-3">
              <a href="{{ route('tools.show', $tool->slug) }}" class="btn-violet flex-grow-1 text-decoration-none py-1 px-3 small">
                <i class="bi bi-play-circle-fill"></i> Launch Tool
              </a>
              <a href="{{ route('tools.show', $tool->slug) }}" class="btn-secondary-custom text-decoration-none py-1 px-3 small" title="View details and source code">
                Details
              </a>
            </div>

            <!-- Footer Stats -->
            <div class="bento-card-footer">
              <div class="d-flex align-items-center gap-1">
                <i class="bi bi-person-circle"></i>
                <span>{{ $tool->user->name ?? 'Community' }}</span>
              </div>
              <div class="d-flex align-items-center gap-3">
                <span title="Likes"><i class="bi bi-heart-fill text-danger me-1"></i>{{ $tool->likes_count }}</span>
                <span title="Forks"><i class="bi bi-git me-1 text-secondary"></i>{{ $tool->forks_count }}</span>
                <span title="Views"><i class="bi bi-eye me-1 text-secondary"></i>{{ $tool->views_count }}</span>
              </div>
            </div>
          </div>
        </div>
      @empty
        <!-- Fallback if catalog is fresh -->
        <div class="col-bento-12">
          <div class="bento-card text-center py-5">
            <div class="bento-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.6rem;">
              <i class="bi bi-tools"></i>
            </div>
            <h3 class="h4 fw-bold">Explore Our Micro-Tools</h3>
            <p class="text-secondary small max-w-md mx-auto mb-4" style="max-width: 480px;">
              Create and run single-purpose web tools instantly. Write standard HTML/JS in AI Code Studio or instruct ChatGPT via MCP.
            </p>
            <a href="{{ route('dashboard.index') }}" class="btn-violet text-decoration-none py-2 px-4 d-inline-flex mx-auto">
              Create Your First Tool
            </a>
          </div>
        </div>
      @endforelse
    </div>
  </div>
</section>

<!-- How It Works (Bento Architecture) -->
<section id="about" class="py-5 border-top border-bottom" style="background: var(--bg-surface); border-color: var(--border-subtle) !important;">
  <div class="container">
    <div class="text-center max-w-lg mx-auto mb-5" style="max-width: 680px; margin: 0 auto;">
      <span class="badge font-mono small py-1 px-2 mb-2" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
        ENGINEERED FOR SPEED
      </span>
      <h2 class="h3 fw-bold mb-2">How MicroTool Hub Works</h2>
      <p class="text-secondary small">
        Lightweight, accessible, single-purpose web tools built with modern standards and zero external tracking.
      </p>
    </div>

    <div class="bento-grid">
      <!-- Feature 1 -->
      <div class="col-bento-4">
        <div class="bento-card h-100">
          <div class="bento-icon-box">
            <i class="bi bi-shield-check"></i>
          </div>
          <h4 class="h5 fw-bold mb-2">1. 100% Client-Side Privacy</h4>
          <p class="text-secondary small mb-0">
            Images, documents, and code are processed entirely inside your local browser sandbox. No file uploads or sensitive data ever touch our servers.
          </p>
        </div>
      </div>

      <!-- Feature 2 -->
      <div class="col-bento-4">
        <div class="bento-card h-100">
          <div class="bento-icon-box">
            <i class="bi bi-code-slash"></i>
          </div>
          <h4 class="h5 fw-bold mb-2">2. AI Code Studio</h4>
          <p class="text-secondary small mb-0">
            Inspect source code, tweak CSS variables, test changes live, and save point-in-time version snapshots directly from your personal dashboard.
          </p>
        </div>
      </div>

      <!-- Feature 3 -->
      <div class="col-bento-4">
        <div class="bento-card h-100">
          <div class="bento-icon-box">
            <i class="bi bi-cpu"></i>
          </div>
          <h4 class="h5 fw-bold mb-2">3. ChatGPT MCP Integration</h4>
          <p class="text-secondary small mb-0">
            Connect ChatGPT directly to your workspace using the Model Context Protocol (MCP). Generate, edit, and launch custom tools via simple conversation.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ChatGPT MCP Call To Action -->
<section id="chatgpt-connect" class="py-5">
  <div class="container">
    <div class="bento-card p-4 p-md-5" style="background: linear-gradient(135deg, var(--bg-card) 0%, rgba(139, 92, 246, 0.08) 100%); border-color: var(--violet-border);">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <div class="d-inline-flex align-items-center gap-2 mb-2">
            <span class="mcp-pill">AI CONNECTOR</span>
            <span class="text-secondary small">Model Context Protocol 2026</span>
          </div>
          <h2 class="h3 fw-bold mb-3">Connect ChatGPT Directly to Your Tool Workspace</h2>
          <p class="text-secondary mb-4 small" style="line-height: 1.6;">
            Empower ChatGPT to automatically build, validate, and publish web micro-tools in your account. Fully supports RFC 9728 discovery and OAuth 2.1 code grant.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('dashboard.tokens') }}" class="btn-violet text-decoration-none py-2 px-4 small">
              <i class="bi bi-key-fill"></i> Get MCP Access Token
            </a>
            <a href="{{ url('/.well-known/oauth-protected-resource') }}" target="_blank" class="btn-secondary-custom text-decoration-none py-2 px-3 small">
              <i class="bi bi-file-code"></i> RFC 9728 Discovery
            </a>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="p-3 rounded-3 font-mono small border" style="background: var(--bg-secondary); border-color: var(--border-subtle) !important;">
            <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: var(--border-subtle) !important;">
              <span class="text-secondary small">MCP Endpoint</span>
              <span class="badge py-1 px-2 font-mono" style="background: var(--violet-soft); color: var(--violet-500);">Streamable HTTP</span>
            </div>
            <code class="d-block text-break mb-2" style="color: var(--violet-500);">{{ url('/mcp') }}</code>
            <div class="text-secondary small">
              <i class="bi bi-check-circle-fill text-success me-1"></i> OAuth 2.1 Authorized<br>
              <i class="bi bi-check-circle-fill text-success me-1"></i> Client-Side Sandbox Preview<br>
              <i class="bi bi-check-circle-fill text-success me-1"></i> Zero-Data Exfiltration (CSP)
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
