@extends('layouts.app')

@section('title', $user->name . ' (@' . ($user->username ?? $user->id) . ') — MicroTool Hub')

@section('content')
<div class="container py-4">
  <div class="row g-4">
    <!-- User Profile Sidebar -->
    <div class="col-lg-4">
      <div class="bento-card text-center p-4">
        <div class="bento-icon-box mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
          <i class="bi bi-person-circle"></i>
        </div>
        <h2 class="h4 fw-bold mb-1 text-body">{{ $user->name }}</h2>
        <div class="text-secondary small mb-3">@@{{ $user->username ?? $user->id }}</div>
        <p class="text-secondary small mb-4">{{ $user->bio ?? 'Active creator in the MicroTool Hub community.' }}</p>

        <div class="row g-2 border-top pt-3 text-center" style="border-color: var(--border-subtle) !important;">
          <div class="col-6">
            <div class="fs-4 fw-bold" style="color: var(--violet-500);">{{ $tools->count() }}</div>
            <div class="text-secondary small">Published Tools</div>
          </div>
          <div class="col-6">
            <div class="fs-4 fw-bold" style="color: var(--color-success);">{{ $tools->sum('forks_count') }}</div>
            <div class="text-secondary small">Forks</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Published Tools -->
    <div class="col-lg-8">
      <h2 class="h4 fw-bold mb-4 text-body">Published Tools by {{ $user->name }}</h2>
      <div class="row g-3">
        @forelse($tools as $tool)
          <div class="col-md-6">
            <div class="bento-card h-100 p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge py-1 px-2 font-mono small text-secondary bg-body-tertiary border">{{ $tool->category?->name ?? 'Utility' }}</span>
                <span class="badge font-mono small text-secondary bg-body-tertiary border">{{ $tool->current_version }}</span>
              </div>
              <h3 class="h6 fw-bold mb-1">
                <a href="{{ route('tools.show', $tool->slug) }}" class="text-decoration-none text-body">{{ $tool->name }}</a>
              </h3>
              <p class="text-secondary small flex-grow-1 mb-3">{{ Str::limit($tool->description, 90) }}</p>
              <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-secondary" style="border-color: var(--border-subtle) !important;">
                <span><i class="bi bi-heart-fill text-danger me-1"></i> {{ $tool->likes_count }}</span>
                <a href="{{ route('tools.show', $tool->slug) }}" class="btn-violet btn-sm text-decoration-none py-1 px-3 small">Launch</a>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12 text-center py-5">
            <div class="bento-card text-center py-5">
              <p class="text-secondary mb-0">No public tools published yet by this creator.</p>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
