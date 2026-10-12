@extends('layouts.app')

@section('title', '404 — Micro-Tool or Page Not Found')

@section('content')
<div class="container py-5 text-center">
  <div class="display-1 fw-bold text-info mb-2 font-mono">404</div>
  <h2 class="fw-bold mb-3">Tool or Page Not Found</h2>
  <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
    The micro-tool or workspace you are looking for does not exist, has been removed, or is set to private.
  </p>
  <div class="d-flex justify-content-center gap-2">
    <a href="{{ route('home') }}" class="btn btn-gradient">Return Home</a>
    <a href="{{ route('explore') }}" class="btn btn-outline-secondary">Explore Community</a>
  </div>
</div>
@endsection
