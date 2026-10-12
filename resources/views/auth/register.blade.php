@extends('layouts.app')

@section('title', 'Create Account — MicroTool Hub')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card border shadow-sm p-4">
        <div class="text-center mb-4">
          <img src="{{ asset('assets/icon.png') }}" alt="Logo" width="48" height="48" class="rounded mb-2">
          <h3 class="fw-bold mb-1">Join MicroTool Hub</h3>
          <p class="text-muted small">Create an account to build, preview, and publish web tools.</p>
        </div>

        @if($errors->any())
          <div class="alert alert-danger py-2 small">
            {{ $errors->first() }}
          </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label small fw-semibold">Your Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Email Address</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Minimum 8 characters">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-gradient w-100 mb-3">Create Free Account</button>

          <div class="text-center text-muted small">
            Already have an account? <a href="{{ route('login') }}" class="text-info text-decoration-none fw-semibold">Sign In</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
