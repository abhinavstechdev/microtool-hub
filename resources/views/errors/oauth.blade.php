@extends('layouts.app')

@section('title', 'OAuth Error — MicroTool Hub')

@section('content')
<div class="container py-5 text-center">
  <div class="display-3 text-danger mb-3"><i class="bi bi-shield-x"></i></div>
  <h2 class="fw-bold mb-3">OAuth Authorization Error</h2>
  <div class="alert alert-danger d-inline-block text-start mb-4" style="max-width: 500px;">
    {{ $error ?? 'Invalid authorization request parameters.' }}
  </div>
  <div>
    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Return to Home</a>
  </div>
</div>
@endsection
