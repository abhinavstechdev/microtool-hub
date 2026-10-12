@extends('layouts.app')

@section('title', 'Sign In — MicroTool Hub')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card border shadow-sm p-4">
        <div class="text-center mb-4">
          <img src="{{ asset('assets/icon.png') }}" alt="Logo" width="48" height="48" class="rounded mb-2">
          <h3 class="fw-bold mb-1">Welcome Back</h3>
          <p class="text-muted small">Sign in to manage your micro-tools and MCP connectors.</p>
        </div>

        @if($errors->any())
          <div class="alert alert-danger py-2 small">
            {{ $errors->first() }}
          </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label small fw-semibold">Email Address</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" name="remember" class="form-check-input" id="rememberCheck">
            <label class="form-check-label small text-muted" for="rememberCheck">Remember me</label>
          </div>

          <button type="submit" class="btn btn-gradient w-100 mb-3">Sign In</button>

          <div class="text-center text-muted small">
            Don't have an account? <a href="{{ route('register') }}" class="text-info text-decoration-none fw-semibold">Create Account</a>
          </div>

          <div class="border-top mt-3 pt-3 text-center text-muted" style="font-size: 0.75rem;">
            Demo Login: <code>creator@microtoolhub.com</code> / <code>password123</code>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
