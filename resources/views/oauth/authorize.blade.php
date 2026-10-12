@extends('layouts.app')

@section('title', 'Authorize Application — MicroTool Hub')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card border shadow-lg p-4">
        <div class="text-center mb-4">
          <div class="d-inline-flex p-3 rounded-circle bg-info-subtle text-info mb-2 fs-2">
            <i class="bi bi-robot"></i>
          </div>
          <h4 class="fw-bold mb-1">Authorize Application</h4>
          <p class="text-muted small"><strong>{{ $client->name }}</strong> is requesting access to your MicroTool Hub account.</p>
        </div>

        <div class="card bg-body-tertiary border p-3 mb-4">
          <h6 class="fw-bold small text-uppercase text-muted mb-2">Requested Scopes</h6>
          <ul class="list-unstyled mb-0 small text-muted">
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Read Workspaces:</strong> View your micro-tool projects and files.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Code & Write:</strong> Create new projects and write code to files.</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Create Snapshots:</strong> Take version backups of tools.</li>
            <li class="mb-0"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>In-Chat Previews:</strong> Render live interactive widgets in chat.</li>
          </ul>
        </div>

        <div class="alert alert-warning py-2 small mb-4">
          <i class="bi bi-shield-lock me-1"></i> Authorizing grants access to <strong>{{ $user->email }}</strong>'s workspace.
        </div>

        <form action="{{ route('oauth.approve') }}" method="POST">
          @csrf
          <input type="hidden" name="client_id" value="{{ $client->client_id }}">
          <input type="hidden" name="redirect_uri" value="{{ $redirectUri }}">
          <input type="hidden" name="state" value="{{ $state }}">
          <input type="hidden" name="scope" value="{{ $scope }}">
          <input type="hidden" name="code_challenge" value="{{ $codeChallenge }}">
          <input type="hidden" name="code_challenge_method" value="{{ $codeChallengeMethod }}">

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-gradient py-2">Authorize {{ $client->name }}</button>
            <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary py-2">Deny Access</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
