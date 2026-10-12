@extends('layouts.app')

@section('title', $user->name . ' (@' . ($user->username ?? $user->id) . ') — MicroTool Hub')

@section('content')
<div class="container py-4">
  <div class="row g-4">
    <!-- User Sidebar -->
    <div class="col-lg-4">
      <div class="card border p-4 text-center">
        <div class="display-3 text-info mb-3"><i class="bi bi-person-circle"></i></div>
        <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
        <div class="text-muted small mb-3">@@{{ $user->username ?? $user->id }}</div>
        <p class="text-muted small mb-4">{{ $user->bio ?? 'Active creator in the MicroTool Hub community.' }}</p>

        <div class="row g-2 border-top pt-3 text-center">
          <div class="col-6">
            <div class="fs-4 fw-bold text-info">{{ $tools->count() }}</div>
            <div class="text-muted small">Tools</div>
          </div>
          <div class="col-6">
            <div class="fs-4 fw-bold text-success">{{ $tools->sum('forks_count') }}</div>
            <div class="text-muted small">Forks</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Published Tools -->
    <div class="col-lg-8">
      <h4 class="fw-bold mb-4">Published Tools by {{ $user->name }}</h4>
      <div class="row g-3">
        @forelse($tools as $tool)
          <div class="col-md-6">
            <div class="card h-100 tool-card p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-body-tertiary border text-info small">{{ $tool->category?->name ?? 'Utility' }}</span>
                <span class="badge bg-secondary-subtle text-secondary font-mono">{{ $tool->current_version }}</span>
              </div>
              <h6 class="fw-bold mb-1">
                <a href="{{ route('tools.show', $tool->slug) }}" class="text-decoration-none text-body">{{ $tool->name }}</a>
              </h6>
              <p class="text-muted small flex-grow-1">{{ Str::limit($tool->description, 80) }}</p>
              <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                <span><i class="bi bi-heart text-danger"></i> {{ $tool->likes_count }}</span>
                <a href="{{ route('preview.show', $tool->slug) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0">Launch</a>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12 text-center py-5">
            <p class="text-muted">No public tools published yet.</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
