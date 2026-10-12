@extends('layouts.app')

@section('title', 'MCP Access Tokens & Connectors — MicroTool Hub')

@section('content')
<div class="container py-4">
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="fw-bold mb-1">MCP Access Tokens</h2>
          <p class="text-muted small mb-0">Authorized tokens allowing ChatGPT and AI clients to interact with your workspace.</p>
        </div>
        <form action="{{ route('dashboard.tokens.generate') }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-sm btn-gradient">
            <i class="bi bi-plus-lg me-1"></i> Generate New Token
          </button>
        </form>
      </div>

      <!-- New Token Banner -->
      @if(session('new_token'))
        <div class="alert alert-success border-success p-3 mb-4">
          <h6 class="fw-bold text-success mb-2"><i class="bi bi-key-fill me-1"></i> New Access Token Generated</h6>
          <p class="small mb-2">Copy this token now! For your security, it will not be displayed again:</p>
          <div class="input-group">
            <input type="text" id="newTokenVal" class="form-control font-mono small bg-body" value="{{ session('new_token') }}" readonly>
            <button class="btn btn-success" onclick="navigator.clipboard.writeText(document.getElementById('newTokenVal').value); this.textContent = 'Copied!';">
              Copy Token
            </button>
          </div>
        </div>
      @endif

      <!-- Active Tokens List -->
      <div class="card border p-3 mb-4">
        <h6 class="fw-bold mb-3">Active Tokens ({{ $tokens->where('revoked', false)->count() }})</h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead>
              <tr class="text-muted">
                <th>Client / Label</th>
                <th>Token Hash</th>
                <th>Expires</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tokens as $tok)
                <tr>
                  <td><strong>{{ $tok->client_id }}</strong></td>
                  <td class="font-mono text-muted">{{ substr($tok->token_hash, 0, 12) }}…</td>
                  <td>{{ $tok->expires_at->format('M d, Y') }}</td>
                  <td>
                    @if($tok->revoked)
                      <span class="badge bg-danger-subtle text-danger">Revoked</span>
                    @elseif($tok->expires_at->isPast())
                      <span class="badge bg-secondary-subtle text-secondary">Expired</span>
                    @else
                      <span class="badge bg-success-subtle text-success">Active</span>
                    @endif
                  </td>
                  <td class="text-end">
                    @if(!$tok->revoked)
                      <form action="{{ route('dashboard.tokens.revoke', $tok->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Revoke">Revoke</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No active access tokens yet. Generate one to connect ChatGPT.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Instructions Column -->
    <div class="col-lg-4">
      <div class="card border p-4 bg-body-tertiary">
        <h5 class="fw-bold mb-3"><i class="bi bi-robot text-info me-2"></i>Connecting to ChatGPT</h5>
        <p class="text-muted small">
          Connect your MicroTool Hub workspace to ChatGPT or Codex using the official Model Context Protocol standard:
        </p>

        <h6 class="fw-bold small text-uppercase text-muted mt-3 mb-2">Option A: Streamable HTTP</h6>
        <div class="p-2 bg-body rounded border font-mono small text-info mb-2 text-break">
          {{ url('/mcp') }}
        </div>
        <p class="text-muted small">In ChatGPT custom actions or connector settings, add this URL with Bearer token authentication.</p>

        <h6 class="fw-bold small text-uppercase text-muted mt-3 mb-2">Option B: OAuth 2.1</h6>
        <p class="text-muted small">
          When configuring an Agent Plugin in ChatGPT Work, OpenAI discovers our RFC 9728 metadata automatically at:
        </p>
        <code class="d-block p-2 bg-body rounded border font-mono small text-muted mb-3 text-break">
          {{ url('/.well-known/oauth-protected-resource') }}
        </code>

        <div class="alert alert-info py-2 small mb-0">
          <strong>Security Tip:</strong> All workspace file writes are strictly confined to your private sandbox with automatic snapshot rollbacks.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
