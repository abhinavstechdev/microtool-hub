@extends('layouts.app')

@section('title', 'MicroTool Hub — Small Tools. Big Productivity.')
@section('meta_description', 'Free online tools to convert, edit, calculate, and create — all in one place. Powered by Laravel and ChatGPT MCP.')

@section('content')
<!-- Hero Section (Dark Navy & Refined Radial Lighting) -->
<section class="hero-navy">
  <div class="container text-center">
    <!-- Eyebrow Badge -->
    <div class="d-inline-flex align-items-center mb-3">
      <span class="hero-tag">
        <i class="bi bi-stars" style="color: var(--violet-400);"></i>
        <span>100% CLIENT-SIDE &bull; CHATGPT MCP READY</span>
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

    <!-- Large Prominent Tool Search with Live Autocomplete -->
    <div class="hero-search-wrapper" id="heroSearchWrapper">
      <form action="{{ route('explore') }}" method="GET" class="hero-search-box" id="heroSearchForm">
        <i class="bi bi-search hero-search-icon"></i>
        <input 
          type="search" 
          name="q" 
          id="heroSearchInput"
          class="hero-search-input" 
          placeholder="Search formatters, converters, calculators, image tools..." 
          aria-label="Search micro-tools"
          autocomplete="off"
          value="{{ request('q') }}"
        >
        <button class="hero-search-btn btn-violet" type="submit" id="heroSearchBtn">
          <span>Search</span>
          <i class="bi bi-arrow-right"></i>
        </button>
      </form>

      <!-- Live Autocomplete Suggestions Box -->
      <div class="hero-search-suggestions d-none" id="heroSuggestionsBox" role="listbox">
        <!-- Dynamically populated via JS -->
      </div>
    </div>

    <!-- Popular Search Suggestions Chips -->
    <div class="search-chips">
      <span class="search-chips-label">
        <i class="bi bi-lightning-charge-fill" style="color: var(--violet-400);"></i> Popular:
      </span>
      <a href="{{ route('explore', ['q' => 'JPG to PNG']) }}" class="search-chip">JPG to PNG</a>
      <a href="{{ route('explore', ['q' => 'Image Resizer']) }}" class="search-chip">Image Resizer</a>
      <a href="{{ route('tools.show', 'json-formatter') }}" class="search-chip">JSON Formatter</a>
      <a href="{{ route('tools.show', 'box-shadow-generator') }}" class="search-chip">CSS Box-Shadow</a>
      <a href="{{ route('explore', ['category' => 'converters']) }}" class="search-chip">Converters</a>
      <a href="{{ route('explore', ['category' => 'calculators']) }}" class="search-chip">Calculators</a>
    </div>

    <!-- Quick Stats Row -->
    <div class="row g-3 justify-content-center mt-3 pt-3">
      <div class="col-4 col-md-3">
        <div class="fs-4 fw-bold" style="color: var(--violet-400);">{{ max(1, $stats['total_tools']) }}+</div>
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

    <!-- Category Chips Row -->
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

<!-- Tool Discovery Section (Tabs: Featured, Most Popular, Recently Added) -->
<section class="py-5" id="tools-discovery">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
      <div>
        <div class="d-inline-flex align-items-center gap-2 mb-2">
          <span class="badge font-mono small py-1 px-2" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
            CURATED CATALOG
          </span>
        </div>
        <h2 class="h3 fw-bold mb-1">Discover Web Micro-Tools</h2>
        <p class="text-secondary small mb-0">High-performance tools running client-side with instant preview and export.</p>
      </div>

      <!-- Discovery Switcher Tabs -->
      <div class="discovery-tabs" role="tablist">
        <button class="discovery-tab-btn active" id="tab-featured" data-target="panel-featured" role="tab" aria-selected="true">
          <i class="bi bi-stars"></i> Featured
        </button>
        <button class="discovery-tab-btn" id="tab-popular" data-target="panel-popular" role="tab" aria-selected="false">
          <i class="bi bi-fire"></i> Most Popular
        </button>
        <button class="discovery-tab-btn" id="tab-recent" data-target="panel-recent" role="tab" aria-selected="false">
          <i class="bi bi-clock-history"></i> Recent
        </button>
      </div>
    </div>

    <!-- Panel 1: Featured Tools -->
    <div class="discovery-panel" id="panel-featured" role="tabpanel">
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @forelse($featuredTools as $tool)
          <div class="col">
            <div class="tool-card-interactive" onclick="window.location='{{ route('tools.show', $tool->slug) }}'">
              <!-- Card Top -->
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-2">
                  <div class="bento-icon-box mb-0" style="width: 36px; height: 36px; font-size: 1.05rem;">
                    <i class="bi {{ $tool->category?->icon ?? 'bi-cpu' }}"></i>
                  </div>
                  <div>
                    <span class="badge py-1 px-2 font-mono small" style="background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-subtle);">
                      {{ $tool->category?->name ?? 'Utility' }}
                    </span>
                    <span class="badge py-1 px-2 font-mono small ms-1" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
                      ★ Featured
                    </span>
                  </div>
                </div>
                <span class="badge font-mono small text-secondary bg-body-tertiary border">
                  {{ $tool->current_version }}
                </span>
              </div>

              <!-- Title & Description -->
              <h3 class="tool-card-title">
                <a href="{{ route('tools.show', $tool->slug) }}">{{ $tool->name }}</a>
              </h3>
              <p class="tool-card-desc">
                {{ Str::limit($tool->description, 130) }}
              </p>

              <!-- Card Actions -->
              <div class="d-flex gap-2 mb-3">
                <a href="{{ route('tools.show', $tool->slug) }}" class="btn-violet flex-grow-1 text-decoration-none py-1 px-3 small" onclick="event.stopPropagation();">
                  <i class="bi bi-play-circle-fill"></i> Launch Tool
                </a>
                <a href="{{ route('tools.show', $tool->slug) }}" class="btn-secondary-custom text-decoration-none py-1 px-3 small" onclick="event.stopPropagation();" title="Inspect details and source">
                  Details
                </a>
              </div>

              <!-- Card Footer Stats -->
              <div class="tool-card-footer">
                <div class="d-flex align-items-center gap-1">
                  <i class="bi bi-person-circle"></i>
                  <span>{{ $tool->user->name ?? 'Community' }}</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <span title="Likes"><i class="bi bi-heart-fill text-danger me-1"></i>{{ $tool->likes_count }}</span>
                  <span title="Views"><i class="bi bi-eye me-1 text-secondary"></i>{{ $tool->views_count }}</span>
                  <span title="Forks"><i class="bi bi-git me-1 text-secondary"></i>{{ $tool->forks_count }}</span>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="bento-card text-center py-5">
              <i class="bi bi-tools fs-1 text-secondary mb-2 d-block"></i>
              <h4 class="h5 fw-bold">No Featured Tools Yet</h4>
              <p class="text-secondary small mb-3">Browse all tools in the catalog or create one in Studio.</p>
              <a href="{{ route('explore') }}" class="btn-violet text-decoration-none py-1 px-3 small d-inline-flex mx-auto">Explore All Tools</a>
            </div>
          </div>
        @endforelse
      </div>
    </div>

    <!-- Panel 2: Most Popular Tools -->
    <div class="discovery-panel d-none" id="panel-popular" role="tabpanel">
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @forelse($popularTools as $tool)
          <div class="col">
            <div class="tool-card-interactive" onclick="window.location='{{ route('tools.show', $tool->slug) }}'">
              <!-- Card Top -->
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-2">
                  <div class="bento-icon-box mb-0" style="width: 36px; height: 36px; font-size: 1.05rem;">
                    <i class="bi {{ $tool->category?->icon ?? 'bi-cpu' }}"></i>
                  </div>
                  <div>
                    <span class="badge py-1 px-2 font-mono small" style="background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-subtle);">
                      {{ $tool->category?->name ?? 'Utility' }}
                    </span>
                    <span class="badge py-1 px-2 font-mono small ms-1" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">
                      🔥 Popular
                    </span>
                  </div>
                </div>
                <span class="badge font-mono small text-secondary bg-body-tertiary border">
                  {{ $tool->current_version }}
                </span>
              </div>

              <!-- Title & Description -->
              <h3 class="tool-card-title">
                <a href="{{ route('tools.show', $tool->slug) }}">{{ $tool->name }}</a>
              </h3>
              <p class="tool-card-desc">
                {{ Str::limit($tool->description, 130) }}
              </p>

              <!-- Card Actions -->
              <div class="d-flex gap-2 mb-3">
                <a href="{{ route('tools.show', $tool->slug) }}" class="btn-violet flex-grow-1 text-decoration-none py-1 px-3 small" onclick="event.stopPropagation();">
                  <i class="bi bi-play-circle-fill"></i> Launch Tool
                </a>
                <a href="{{ route('tools.show', $tool->slug) }}" class="btn-secondary-custom text-decoration-none py-1 px-3 small" onclick="event.stopPropagation();">
                  Details
                </a>
              </div>

              <!-- Card Footer Stats -->
              <div class="tool-card-footer">
                <div class="d-flex align-items-center gap-1">
                  <i class="bi bi-person-circle"></i>
                  <span>{{ $tool->user->name ?? 'Community' }}</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <span title="Likes"><i class="bi bi-heart-fill text-danger me-1"></i>{{ $tool->likes_count }}</span>
                  <span title="Views"><i class="bi bi-eye me-1 text-secondary"></i>{{ $tool->views_count }}</span>
                  <span title="Forks"><i class="bi bi-git me-1 text-secondary"></i>{{ $tool->forks_count }}</span>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="bento-card text-center py-5">
              <i class="bi bi-fire fs-1 text-secondary mb-2 d-block"></i>
              <h4 class="h5 fw-bold">Popular Tools</h4>
              <p class="text-secondary small mb-3">Browse all available tools in our catalog.</p>
              <a href="{{ route('explore') }}" class="btn-violet text-decoration-none py-1 px-3 small d-inline-flex mx-auto">Explore Catalog</a>
            </div>
          </div>
        @endforelse
      </div>
    </div>

    <!-- Panel 3: Recently Added Tools -->
    <div class="discovery-panel d-none" id="panel-recent" role="tabpanel">
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @forelse($recentTools as $tool)
          <div class="col">
            <div class="tool-card-interactive" onclick="window.location='{{ route('tools.show', $tool->slug) }}'">
              <!-- Card Top -->
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-2">
                  <div class="bento-icon-box mb-0" style="width: 36px; height: 36px; font-size: 1.05rem;">
                    <i class="bi {{ $tool->category?->icon ?? 'bi-cpu' }}"></i>
                  </div>
                  <div>
                    <span class="badge py-1 px-2 font-mono small" style="background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-subtle);">
                      {{ $tool->category?->name ?? 'Utility' }}
                    </span>
                    <span class="badge py-1 px-2 font-mono small ms-1" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);">
                      ✨ New
                    </span>
                  </div>
                </div>
                <span class="badge font-mono small text-secondary bg-body-tertiary border">
                  {{ $tool->current_version }}
                </span>
              </div>

              <!-- Title & Description -->
              <h3 class="tool-card-title">
                <a href="{{ route('tools.show', $tool->slug) }}">{{ $tool->name }}</a>
              </h3>
              <p class="tool-card-desc">
                {{ Str::limit($tool->description, 130) }}
              </p>

              <!-- Card Actions -->
              <div class="d-flex gap-2 mb-3">
                <a href="{{ route('tools.show', $tool->slug) }}" class="btn-violet flex-grow-1 text-decoration-none py-1 px-3 small" onclick="event.stopPropagation();">
                  <i class="bi bi-play-circle-fill"></i> Launch Tool
                </a>
                <a href="{{ route('tools.show', $tool->slug) }}" class="btn-secondary-custom text-decoration-none py-1 px-3 small" onclick="event.stopPropagation();">
                  Details
                </a>
              </div>

              <!-- Card Footer Stats -->
              <div class="tool-card-footer">
                <div class="d-flex align-items-center gap-1">
                  <i class="bi bi-person-circle"></i>
                  <span>{{ $tool->user->name ?? 'Community' }}</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <span title="Likes"><i class="bi bi-heart-fill text-danger me-1"></i>{{ $tool->likes_count }}</span>
                  <span title="Views"><i class="bi bi-eye me-1 text-secondary"></i>{{ $tool->views_count }}</span>
                  <span title="Forks"><i class="bi bi-git me-1 text-secondary"></i>{{ $tool->forks_count }}</span>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="bento-card text-center py-5">
              <i class="bi bi-clock-history fs-1 text-secondary mb-2 d-block"></i>
              <h4 class="h5 fw-bold">Recently Added</h4>
              <p class="text-secondary small mb-3">Browse all tools or create a new micro-tool in AI Code Studio.</p>
              <a href="{{ route('dashboard.index') }}" class="btn-violet text-decoration-none py-1 px-3 small d-inline-flex mx-auto">Create Tool</a>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>

<!-- How It Works (Client-Side Isolation Architecture) -->
<section id="about" class="py-5 border-top border-bottom" style="background: var(--bg-surface); border-color: var(--border-subtle) !important;">
  <div class="container">
    <div class="text-center max-w-lg mx-auto mb-5" style="max-width: 680px; margin: 0 auto;">
      <span class="badge font-mono small py-1 px-2 mb-2" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
        ENGINEERED FOR SPEED & PRIVACY
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // 1. Tool Discovery Tabs Switcher
  const tabButtons = document.querySelectorAll('.discovery-tab-btn');
  const panels = document.querySelectorAll('.discovery-panel');

  tabButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      tabButtons.forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      panels.forEach(p => p.classList.add('d-none'));

      this.classList.add('active');
      this.setAttribute('aria-selected', 'true');
      const targetId = this.getAttribute('data-target');
      const targetPanel = document.getElementById(targetId);
      if (targetPanel) {
        targetPanel.classList.remove('d-none');
      }
    });
  });

  // 2. Live Autocomplete for Main Search Bar
  const searchInput = document.getElementById('heroSearchInput');
  const searchForm = document.getElementById('heroSearchForm');
  const suggestionsBox = document.getElementById('heroSuggestionsBox');
  const searchWrapper = document.getElementById('heroSearchWrapper');
  let debounceTimer = null;
  let activeIndex = -1;

  function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function highlightMatch(text, query) {
    if (!query) return escapeHtml(text);
    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return escapeHtml(text).replace(regex, '<mark style="background: rgba(139, 92, 246, 0.35); color: inherit; padding: 0 2px; border-radius: 3px;">$1</mark>');
  }

  function renderSuggestions(data, query) {
    if (!suggestionsBox) return;

    if (!data.tools || data.tools.length === 0) {
      suggestionsBox.innerHTML = `
        <div class="hero-suggestion-empty">
          <i class="bi bi-search text-secondary d-block fs-4 mb-2"></i>
          <div>No tools found matching "<strong>${escapeHtml(query)}</strong>"</div>
          <div class="small text-muted mt-1">Press Enter to search the full directory or try categories above.</div>
        </div>
      `;
      suggestionsBox.classList.remove('d-none');
      activeIndex = -1;
      return;
    }

    let html = '';
    data.tools.forEach((tool, idx) => {
      html += `
        <a href="${tool.url}" class="hero-suggestion-item" data-index="${idx}" role="option">
          <div class="hero-suggestion-icon">
            <i class="bi ${tool.category_icon || 'bi-cpu'}"></i>
          </div>
          <div class="hero-suggestion-content">
            <div class="hero-suggestion-name">${highlightMatch(tool.name, query)}</div>
            <div class="hero-suggestion-desc">${escapeHtml(tool.description || '')}</div>
          </div>
          <span class="hero-suggestion-badge">${escapeHtml(tool.category)}</span>
        </a>
      `;
    });

    html += `
      <div class="hero-suggestion-footer">
        <span>${data.count} matching ${data.count === 1 ? 'tool' : 'tools'}</span>
        <span style="color: var(--violet-400);">Press Enter for all results &rarr;</span>
      </div>
    `;

    suggestionsBox.innerHTML = html;
    suggestionsBox.classList.remove('d-none');
    activeIndex = -1;
  }

  function fetchSuggestions(query) {
    if (!query || query.length < 1) {
      if (suggestionsBox) {
        suggestionsBox.innerHTML = '';
        suggestionsBox.classList.add('d-none');
      }
      return;
    }

    fetch(`/api/tools/quick-search?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        renderSuggestions(data, query);
      })
      .catch(() => {
        if (suggestionsBox) suggestionsBox.classList.add('d-none');
      });
  }

  if (searchInput && suggestionsBox) {
    searchInput.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      const val = this.value.trim();
      debounceTimer = setTimeout(() => {
        fetchSuggestions(val);
      }, 150);
    });

    searchInput.addEventListener('keydown', function(e) {
      const items = suggestionsBox.querySelectorAll('.hero-suggestion-item');
      if (suggestionsBox.classList.contains('d-none') || items.length === 0) {
        return;
      }

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = (activeIndex + 1) % items.length;
        updateActiveItem(items);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = (activeIndex - 1 + items.length) % items.length;
        updateActiveItem(items);
      } else if (e.key === 'Enter') {
        if (activeIndex >= 0 && items[activeIndex]) {
          e.preventDefault();
          window.location.href = items[activeIndex].getAttribute('href');
        }
      } else if (e.key === 'Escape') {
        suggestionsBox.classList.add('d-none');
        activeIndex = -1;
      }
    });

    function updateActiveItem(items) {
      items.forEach((item, idx) => {
        if (idx === activeIndex) {
          item.classList.add('active');
          item.scrollIntoView({ block: 'nearest' });
        } else {
          item.classList.remove('active');
        }
      });
    }

    // Close on outside click
    document.addEventListener('click', function(e) {
      if (searchWrapper && !searchWrapper.contains(e.target)) {
        suggestionsBox.classList.add('d-none');
        activeIndex = -1;
      }
    });
  }

  // 3. Global '/' Keyboard Shortcut to Focus Search
  document.addEventListener('keydown', function(e) {
    if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
      e.preventDefault();
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      } else {
        const navSearch = document.getElementById('navSearchInput');
        if (navSearch) navSearch.focus();
      }
    }
  });
});
</script>
@endpush
