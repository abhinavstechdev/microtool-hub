@extends('layouts.app')

@section('title', 'Explore Web Micro-Tools — MicroTool Hub')

@section('content')
<div class="container py-4">
  <!-- Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h1 class="fw-bold mb-1">Explore Micro-Tools</h1>
      <p class="text-muted small mb-0">Browse calculators, formatters, generators, and widgets built by the community.</p>
    </div>
    @auth
      <a href="{{ route('dashboard.index') }}" class="btn btn-sm btn-gradient">
        <i class="bi bi-plus-lg me-1"></i> Create Tool
      </a>
    @endauth
  </div>

  <!-- Filters & Search Bar -->
  <div class="card p-3 mb-4 bg-body-tertiary border">
    <form action="{{ route('explore') }}" method="GET" class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text bg-body border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="q" class="form-control bg-body border-start-0" placeholder="Search keywords..." value="{{ $query }}">
        </div>
      </div>
      <div class="col-md-4">
        <select name="category" class="form-select bg-body" onchange="this.form.submit()">
          <option value="">All Categories</option>
          @foreach($categories as $category)
            <option value="{{ $category->slug }}" {{ $catSlug === $category->slug ? 'selected' : '' }}>
              {{ $category->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="sort" class="form-select bg-body" onchange="this.form.submit()">
          <option value="popular" {{ $sort === 'popular' ? 'selected' : '' }}>Most Liked</option>
          <option value="recent" {{ $sort === 'recent' ? 'selected' : '' }}>Recently Added</option>
          <option value="forked" {{ $sort === 'forked' ? 'selected' : '' }}>Most Forked</option>
        </select>
      </div>
    </form>
  </div>

  <!-- Tools Grid -->
  <div class="row g-4">
    @forelse($tools as $tool)
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 tool-card p-3 d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge bg-body-tertiary border text-info small">
              <i class="bi {{ $tool->category?->icon ?? 'bi-tools' }} me-1"></i> {{ $tool->category?->name ?? 'Utility' }}
            </span>
            <span class="badge bg-secondary-subtle text-secondary font-mono">{{ $tool->current_version }}</span>
          </div>

          <h5 class="fw-bold mb-2">
            <a href="{{ route('tools.show', $tool->slug) }}" class="text-decoration-none text-body">
              {{ $tool->name }}
            </a>
          </h5>

          <p class="text-muted small flex-grow-1">
            {{ Str::limit($tool->description, 120) }}
          </p>

          <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-3 small text-muted">
            <div class="d-flex align-items-center gap-1">
              <i class="bi bi-person-circle"></i>
              <a href="{{ route('profile.show', $tool->user->username ?? $tool->user->id) }}" class="text-muted text-decoration-none">
                {{ $tool->user->name }}
              </a>
            </div>
            <div class="d-flex gap-3">
              <span><i class="bi bi-heart-fill text-danger me-1"></i>{{ $tool->likes_count }}</span>
              <span><i class="bi bi-git me-1"></i>{{ $tool->forks_count }}</span>
              <span><i class="bi bi-eye me-1"></i>{{ $tool->views_count }}</span>
            </div>
          </div>

          <div class="mt-3 d-flex gap-2">
            <a href="{{ route('preview.show', $tool->slug) }}" target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1">
              <i class="bi bi-play-circle me-1"></i> Launch
            </a>
            <a href="{{ route('tools.show', $tool->slug) }}" class="btn btn-sm btn-outline-secondary">
              Details
            </a>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-center py-5">
        <div class="fs-1 text-muted mb-3"><i class="bi bi-search"></i></div>
        <h4>No micro-tools found</h4>
        <p class="text-muted small">Try adjusting your search criteria or explore another category.</p>
        <a href="{{ route('explore') }}" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
      </div>
    @endforelse
  </div>

  <!-- Pagination -->
  <div class="mt-5 d-flex justify-content-center">
    {{ $tools->links('pagination::bootstrap-5') }}
  </div>
</div>
@endsection
