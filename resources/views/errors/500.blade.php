@extends('layouts.app')

@section('title', '500 — Server Error')

@section('content')
<div class="container py-5 text-center">
  <div class="display-1 fw-bold text-warning mb-2 font-mono">500</div>
  <h2 class="fw-bold mb-3">Internal Server Error</h2>
  <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
    An unexpected error occurred while executing the request.
  </p>
  <a href="{{ route('home') }}" class="btn btn-gradient">Return Home</a>
</div>
@endsection
