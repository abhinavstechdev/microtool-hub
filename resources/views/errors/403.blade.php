@extends('layouts.app')

@section('title', '403 — Access Forbidden')

@section('content')
<div class="container py-5 text-center">
  <div class="display-1 fw-bold text-danger mb-2 font-mono">403</div>
  <h2 class="fw-bold mb-3">Access Forbidden</h2>
  <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
    You do not have permission to view or modify this private workspace or resource.
  </p>
  <a href="{{ route('home') }}" class="btn btn-gradient">Return Home</a>
</div>
@endsection
