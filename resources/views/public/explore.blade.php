@extends('layouts.app')

@section('title', 'All Web Micro-Tools — MicroTool Hub Directory')
@section('meta_description', 'Search and filter single-purpose web calculators, formatters, generators, and image utilities.')

@section('content')
<div class="container py-4">
  <!-- Header Title -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <div class="d-inline-flex align-items-center gap-2 mb-1">
        <span class="mcp-pill">OPEN DIRECTORY</span>
      </div>
      <h1 class="h2 fw-bold mb-1 text-body">Explore Micro-Tools</h1>
      <p class="text-secondary small mb-0">Browse single-purpose web tools running client-side with zero file uploads.</p>
    </div>
    @auth
      <a href="{{ route('dashboard.index') }}" class="btn-violet btn-sm text-decoration-none">
        <i class="bi bi-plus-lg"></i> Create Tool
      </a>
    @endauth
  </div>

  <!-- Search & Filters Bento Card -->
  <div class="bento-card mb-4 p-3 p-md-4">
    <form action="{{ route('explore') }}" method="GET" class="row g-2 align-items-center">
      <!-- Search Input -->
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--border-subtle);"><i class="bi bi-search text-secondary"></i></span>
          <input 
            type="search" 
            name="q" 
            class="form-control bg-transparent border-start-0 text-body shadow-none" 
            placeholder="Search keywords, tool names, extensions..." 
            value="{{ $query }}"
            style="border-color: var(--border-subtle);"
          >
        </div>
      </div>

      <!-- Category Filter -->
      <div class="col-md-4">
        <select name="category" class="form-select bg-transparent text-body shadow-none" onchange="this.form.submit()" style="border-color: var(--border-subtle);">
          <option value="" class="bg-body">All Categories</option>
          @foreach($categories as $category)
            <option value="{{ $category->slug }}" class="bg-body" {{ $catSlug === $category->slug ? 'selected' : '' }}>
              {{ $category->name }}
            </option>
          @endforeach
        </select>
      </div>

      <!-- Sort Options -->
      <div class="col-md-3">
        <select name="sort" class="form-select bg-transparent text-body shadow-none" onchange="this.form.submit()" style="border-color: var(--border-subtle);">
          <option value="popular" class="bg-body" {{ $sort === 'popular' ? 'selected' : '' }}>Most Liked</option>
          <option value="recent" class="bg-body" {{ $sort === 'recent' ? 'selected' : '' }}>Recently Added</option>
          <option value="forked" class="bg-body" {{ $sort === 'forked' ? 'selected' : '' }}>Most Forked</option>
        </select>
      </div>
    </form>

    <!-- Active Filter Reset Bar -->
    @if($query || $catSlug)
      <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
        <span class="text-secondary small">Filtered results:</span>
        @if($query)
          <span class="badge py-1 px-2 font-mono small" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
            Query: "{{ $query }}"
          </span>
        @endif
        @if($catSlug)
          <span class="badge py-1 px-2 font-mono small" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">
            Category: {{ $catSlug }}
          </span>
        @endif
        <a href="{{ route('explore') }}" class="small text-danger ms-auto text-decoration-none fw-semibold">
          <i class="bi bi-x-circle me-1"></i> Clear Filters
        </a>
      </div>
    @endif
  </div>

  <!-- Bento Grid of Tools -->
  <div class="bento-grid">
    @forelse($tools as $tool)
      <div class="col-bento-4">
        <div class="bento-card h-100">
          <!-- Card Header & Badges -->
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="d-flex align-items-center gap-2">
              <div class="bento-icon-box mb-0">
                <i class="bi {{ $tool->category?->icon ?? 'bi-cpu' }}"></i>
              </div>
              <span class="badge py-1 px-2 font-mono small text-secondary bg-body-tertiary border">
                {{ $tool->category?->name ?? 'Utility' }}
              </span>
            </div>
            <span class="badge font-mono small text-secondary bg-body-tertiary border">
              {{ $tool->current_version }}
            </span>
          </div>

          <!-- Tool Title -->
          <h2 class="bento-card-title">
            <a href="{{ route('tools.show', $tool->slug) }}">
              {{ $tool->name }}
            </a>
          </h2>

          <!-- Description -->
          <p class="bento-card-desc">
            {{ Str::limit($tool->description, 120) }}
          </p>

          <!-- Action Buttons -->
          <div class="d-flex gap-2 mb-3">
            <a href="{{ route('tools.show', $tool->slug) }}" class="btn-violet flex-grow-1 text-decoration-none py-1 px-3 small">
              <i class="bi bi-play-circle-fill"></i> Launch Tool
            </a>
            <a href="{{ route('tools.show', $tool->slug) }}" class="btn-secondary-custom text-decoration-none py-1 px-3 small">
              Details
            </a>
          </div>

          <!-- Card Footer Metadata -->
          <div class="bento-card-footer">
            <div class="d-flex align-items-center gap-1">
              <i class="bi bi-person-circle"></i>
              <a href="{{ route('profile.show', $tool->user->username ?? $tool->user->id) }}" class="text-secondary text-decoration-none">
                {{ $tool->user->name }}
              </a>
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
      <!-- Empty State -->
      <div class="col-bento-12">
        <div class="bento-card text-center py-5">
          <div class="bento-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.6rem;">
            <i class="bi bi-search"></i>
          </div>
          <h3 class="h4 fw-bold">No Micro-Tools Found</h3>
          <p class="text-secondary small max-w-md mx-auto mb-4" style="max-width: 440px;">
            We couldn't find any tools matching your search criteria. Try a different keyword or browse all categories.
          </p>
          <a href="{{ route('explore') }}" class="btn-violet text-decoration-none py-2 px-4 d-inline-flex mx-auto">
            Clear Filters
          </a>
        </div>
      </div>
    @endforelse
  </div>

  <!-- Pagination -->
  @if($tools->hasPages())
    <div class="d-flex justify-content-center mt-5">
      {{ $tools->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>
@endsection
