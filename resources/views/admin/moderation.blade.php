@extends('layouts.app')

@section('title', 'Admin Moderation & AI Security Review — MicroTool Hub')

@section('content')
<div class="moderation-container py-4">
  <!-- Admin Header -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <div class="d-inline-flex align-items-center gap-2 mb-1">
        <span class="badge py-1 px-2 font-mono small" style="background: rgba(220, 38, 38, 0.12); color: #ef4444; border: 1px solid rgba(220, 38, 38, 0.25);">
          ADMINISTRATOR PRIVILEGES
        </span>
      </div>
      <h1 class="h2 fw-bold mb-1 text-body">Tool Approval & Moderation Portal</h1>
      <p class="text-secondary small mb-0">
        Review submitted tools, run automated AI security audits, and approve or reject submissions before they go live in the public catalog.
      </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('dashboard.index') }}" class="btn-secondary-custom btn-sm text-decoration-none">
        <i class="bi bi-speedometer2 me-1"></i> Developer Dashboard
      </a>
      <a href="{{ route('explore') }}" class="btn-outline-violet btn-sm text-decoration-none" target="_blank">
        <i class="bi bi-box-arrow-up-right me-1"></i> Public Catalog
      </a>
    </div>
  </div>

  <!-- Metric Highlights -->
  <div class="stats-grid mb-4">
    <div class="bento-card p-3">
      <div class="text-secondary small text-truncate">Pending Approval</div>
      <div class="fs-3 fw-bold" style="color: var(--violet-500);">{{ $counts['pending'] }}</div>
    </div>
    <div class="bento-card p-3">
      <div class="text-secondary small text-truncate">Published & Live</div>
      <div class="fs-3 fw-bold" style="color: var(--color-success);">{{ $counts['published'] }}</div>
    </div>
    <div class="bento-card p-3">
      <div class="text-secondary small text-truncate">Rejected / Revision</div>
      <div class="fs-3 fw-bold text-danger">{{ $counts['rejected'] }}</div>
    </div>
    <div class="bento-card p-3">
      <div class="text-secondary small text-truncate">Total Workspaces</div>
      <div class="fs-3 fw-bold text-body">{{ $counts['all'] }}</div>
    </div>
  </div>

  <!-- Filter Navigation Tabs -->
  <div class="moderation-filters mb-4 border-bottom pb-3" style="border-color: var(--border-subtle) !important;">
    <a href="{{ route('admin.moderation', ['tab' => 'pending']) }}" class="btn-sm text-decoration-none py-1 px-3 rounded-pill {{ $tab === 'pending' ? 'btn-violet' : 'btn-secondary-custom' }}">
      <i class="bi bi-hourglass-split me-1"></i> Awaiting Approval
      @if($counts['pending'] > 0)
        <span class="badge rounded-pill bg-danger ms-1 font-mono">{{ $counts['pending'] }}</span>
      @endif
    </a>
    <a href="{{ route('admin.moderation', ['tab' => 'published']) }}" class="btn-sm text-decoration-none py-1 px-3 rounded-pill {{ $tab === 'published' ? 'btn-violet' : 'btn-secondary-custom' }}">
      <i class="bi bi-check-circle me-1"></i> Published ({{ $counts['published'] }})
    </a>
    <a href="{{ route('admin.moderation', ['tab' => 'rejected']) }}" class="btn-sm text-decoration-none py-1 px-3 rounded-pill {{ $tab === 'rejected' ? 'btn-violet' : 'btn-secondary-custom' }}">
      <i class="bi bi-x-circle me-1"></i> Rejected ({{ $counts['rejected'] }})
    </a>
    <a href="{{ route('admin.moderation', ['tab' => 'all']) }}" class="btn-sm text-decoration-none py-1 px-3 rounded-pill {{ $tab === 'all' ? 'btn-violet' : 'btn-secondary-custom' }}">
      All Tools ({{ $counts['all'] }})
    </a>
  </div>

  <!-- Tools Moderation Grid -->
  <div class="row g-4">
    @forelse($projects as $tool)
      <div class="col-lg-6">
        <div class="bento-card h-100 p-4">
          <!-- Card Header & Status -->
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div class="min-w-0 flex-grow-1">
              <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                @if($tool->status === 'pending_approval')
                  <span class="badge py-1 px-2 font-mono small text-warning bg-warning-subtle border border-warning-subtle">
                    <i class="bi bi-hourglass-split me-1"></i>PENDING REVIEW
                  </span>
                @elseif($tool->status === 'published')
                  <span class="badge py-1 px-2 font-mono small text-success bg-success-subtle border border-success-subtle">
                    <i class="bi bi-check-circle-fill me-1"></i>LIVE IN CATALOG
                  </span>
                @elseif($tool->status === 'rejected')
                  <span class="badge py-1 px-2 font-mono small text-danger bg-danger-subtle border border-danger-subtle">
                    <i class="bi bi-x-octagon-fill me-1"></i>REJECTED
                  </span>
                @else
                  <span class="badge py-1 px-2 font-mono small text-secondary bg-body-tertiary border">
                    {{ strtoupper($tool->status) }}
                  </span>
                @endif
                <span class="badge py-1 px-2 font-mono small text-secondary bg-body-tertiary border">
                  {{ $tool->category?->name ?? 'Utility' }}
                </span>
              </div>
              <h3 class="h5 fw-bold mb-0 text-body text-break">{{ $tool->name }}</h3>
              <span class="text-secondary small font-mono text-break">/preview/{{ $tool->slug }}</span>
            </div>

            <!-- AI Verdict Badge if already audited -->
            @if($tool->ai_review_verdict)
              <span class="badge font-mono small py-1 px-2 flex-shrink-0" style="background: {{ $tool->ai_review_verdict === 'APPROVED' ? 'var(--color-success-bg)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ $tool->ai_review_verdict === 'APPROVED' ? 'var(--color-success)' : '#ef4444' }}; border: 1px solid {{ $tool->ai_review_verdict === 'APPROVED' ? 'var(--color-success)' : '#ef4444' }};">
                AI: {{ $tool->ai_review_verdict }}
              </span>
            @endif
          </div>

          <!-- Description -->
          <p class="text-secondary small mb-3 flex-grow-1" style="line-height: 1.5;">
            {{ $tool->description ?? 'No description provided.' }}
          </p>

          <!-- Rejection note if present -->
          @if($tool->status === 'rejected' && $tool->rejection_reason)
            <div class="p-2 mb-3 rounded-2 small text-danger border border-danger-subtle" style="background: rgba(220, 38, 38, 0.08);">
              <strong>Rejection reason:</strong> {{ $tool->rejection_reason }}
            </div>
          @endif

          <!-- Creator & Files Meta -->
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-secondary py-2 border-top border-bottom mb-3" style="border-color: var(--border-subtle) !important;">
            <div class="text-break">
              <i class="bi bi-person-circle me-1"></i>
              <strong>{{ $tool->user->name }}</strong> ({{ $tool->user->email }})
            </div>
            <div class="text-nowrap">
              <i class="bi bi-folder2-open me-1"></i>
              <span>{{ $tool->files->count() }} file(s) &bull; {{ round($tool->storage_bytes / 1024, 1) }} KB</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="d-flex flex-wrap gap-2 pt-1">
            <!-- 1. Check with AI / ChatGPT Button -->
            <button class="btn-outline-violet btn-sm d-inline-flex align-items-center gap-1 btn-audit-ai" data-uuid="{{ $tool->uuid }}" data-name="{{ $tool->name }}">
              <i class="bi bi-cpu-fill"></i> Check with AI
            </button>

            <!-- 2. Preview Sandbox Button -->
            <a href="{{ route('preview.show', $tool->slug) }}" target="_blank" class="btn-secondary-custom btn-sm text-decoration-none d-inline-flex align-items-center gap-1">
              <i class="bi bi-box-arrow-up-right"></i> Test Sandbox
            </a>

            <!-- 3. Approve Button -->
            @if($tool->status !== 'published')
              <form action="{{ route('admin.moderation.approve', $tool->uuid) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn-violet btn-sm d-inline-flex align-items-center gap-1">
                  <i class="bi bi-check2-circle"></i> Approve
                </button>
              </form>
            @endif

            <!-- 4. Reject Button -->
            @if($tool->status !== 'rejected')
              <button class="btn-secondary-custom btn-sm text-danger d-inline-flex align-items-center gap-1 btn-reject-modal" data-uuid="{{ $tool->uuid }}" data-name="{{ $tool->name }}">
                <i class="bi bi-x-circle"></i> Reject
              </button>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 py-5 text-center">
        <div class="bento-card py-5 text-center">
          <div class="bento-icon-box mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.6rem;">
            <i class="bi bi-shield-check"></i>
          </div>
          <h3 class="h5 fw-bold mb-1">Queue is Clear!</h3>
          <p class="text-secondary small mb-0">No tools found for the selected tab.</p>
        </div>
      </div>
    @endforelse
  </div>

  <!-- Pagination -->
  @if($projects->hasPages())
    <div class="d-flex justify-content-center mt-5">
      {{ $projects->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>

<!-- AI Security & Quality Audit Modal -->
<div class="modal fade" id="auditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bento-card border-0 p-0 overflow-hidden shadow-lg">
      <div class="modal-header border-bottom py-3 px-4" style="border-color: var(--border-subtle) !important;">
        <div class="d-flex align-items-center gap-2">
          <div class="bento-icon-box mb-0" style="width: 32px; height: 32px; font-size: 0.95rem;">
            <i class="bi bi-cpu-fill"></i>
          </div>
          <div>
            <h5 class="modal-title h6 fw-bold mb-0" id="auditModalTitle">AI Security & Quality Audit</h5>
            <span class="text-secondary small font-mono" id="auditModalToolName"></span>
          </div>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="auditModalBody">
        <!-- Spinner -->
        <div class="text-center py-5" id="auditSpinner">
          <div class="spinner-border text-primary mb-3" role="status" style="color: var(--violet-500) !important;"></div>
          <div class="fw-semibold text-body">Auditing source code with AI...</div>
          <p class="text-secondary small">Inspecting index.html, style.css, app.js for security, network leakage, and CSP compliance.</p>
        </div>
        <!-- Audit Results Container -->
        <div id="auditContent" class="d-none">
          <div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-4" id="auditVerdictBox">
            <div>
              <div class="text-uppercase font-mono small fw-bold" id="auditVerdictLabel">VERDICT</div>
              <h4 class="h5 fw-bold mb-1" id="auditVerdictTitle">All Checks Passed</h4>
              <p class="small mb-0 opacity-75" id="auditVerdictSummary"></p>
            </div>
            <div class="text-end">
              <div class="fs-4 fw-bold font-mono" id="auditRiskScore">0/100</div>
              <div class="small opacity-75">Risk Score</div>
            </div>
          </div>

          <h6 class="fw-bold text-body mb-2">Checklist Findings:</h6>
          <div class="list-group list-group-flush border rounded-3 mb-4 overflow-hidden" id="auditFindingsList" style="border-color: var(--border-subtle) !important;"></div>
        </div>
      </div>
      <div class="modal-footer border-top py-2 px-4" style="border-color: var(--border-subtle) !important;">
        <button type="button" class="btn-secondary-custom btn-sm" data-bs-dismiss="modal">Close</button>
        <form id="auditModalApproveForm" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn-violet btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-check2-circle"></i> Approve Tool Now
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Reject Reason Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bento-card border-0 p-0 overflow-hidden shadow-lg">
      <form id="rejectForm" method="POST">
        @csrf
        <div class="modal-header border-bottom py-3 px-4" style="border-color: var(--border-subtle) !important;">
          <h5 class="modal-title h6 fw-bold text-body" id="rejectModalTitle">Reject Tool Submission</h5>
          <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <p class="text-secondary small mb-3">
            Provide feedback explaining why this tool is rejected and what the creator needs to modify before resubmitting:
          </p>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-body">Rejection Feedback:</label>
            <textarea name="reason" class="form-control text-body shadow-none" rows="4" required placeholder="e.g., The tool has broken buttons on mobile / Contains external network scripts not permitted in sandbox." style="background: var(--bg-surface); border-color: var(--border-subtle);"></textarea>
          </div>
        </div>
        <div class="modal-footer border-top py-2 px-4" style="border-color: var(--border-subtle) !important;">
          <button type="button" class="btn-secondary-custom btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-danger">Confirm Rejection</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
  (function() {
    const auditModalEl = document.getElementById('auditModal');
    const auditModal = new bootstrap.Modal(auditModalEl);
    const rejectModalEl = document.getElementById('rejectModal');
    const rejectModal = new bootstrap.Modal(rejectModalEl);

    // AI Audit Trigger
    document.querySelectorAll('.btn-audit-ai').forEach(btn => {
      btn.addEventListener('click', function() {
        const uuid = this.dataset.uuid;
        const name = this.dataset.name;

        document.getElementById('auditModalToolName').textContent = name;
        document.getElementById('auditSpinner').classList.remove('d-none');
        document.getElementById('auditContent').classList.add('d-none');
        document.getElementById('auditModalApproveForm').action = '/admin/moderation/' + uuid + '/approve';

        auditModal.show();

        fetch('/admin/moderation/' + uuid + '/audit', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            const report = data.report;
            document.getElementById('auditSpinner').classList.add('d-none');
            document.getElementById('auditContent').classList.remove('d-none');

            const box = document.getElementById('auditVerdictBox');
            const label = document.getElementById('auditVerdictLabel');
            const title = document.getElementById('auditVerdictTitle');
            const summary = document.getElementById('auditVerdictSummary');
            const score = document.getElementById('auditRiskScore');

            score.textContent = report.risk_score + '/100';
            summary.textContent = report.summary;

            if (report.verdict === 'APPROVED') {
              box.style.background = 'rgba(22, 163, 74, 0.12)';
              box.style.border = '1px solid #16a34a';
              box.style.color = '#16a34a';
              label.textContent = 'VERDICT: PASSED';
              title.textContent = 'Safe for Public Catalog';
            } else if (report.verdict === 'WARNING') {
              box.style.background = 'rgba(245, 158, 11, 0.12)';
              box.style.border = '1px solid #f59e0b';
              box.style.color = '#d97706';
              label.textContent = 'VERDICT: WARNINGS FOUND';
              title.textContent = 'Manual Review Advised';
            } else {
              box.style.background = 'rgba(220, 38, 38, 0.12)';
              box.style.border = '1px solid #dc2626';
              box.style.color = '#dc2626';
              label.textContent = 'VERDICT: SECURITY RISK FLAGGED';
              title.textContent = 'Critical Issues Detected';
            }

            const list = document.getElementById('auditFindingsList');
            list.innerHTML = '';
            report.findings.forEach(f => {
              const item = document.createElement('div');
              item.className = 'list-group-item bg-transparent d-flex align-items-start gap-2 py-2 px-3';
              item.style.borderColor = 'var(--border-subtle)';
              
              const icon = document.createElement('i');
              if (f.type === 'success') {
                icon.className = 'bi bi-check-circle-fill text-success mt-1';
              } else if (f.type === 'warning') {
                icon.className = 'bi bi-exclamation-triangle-fill text-warning mt-1';
              } else {
                icon.className = 'bi bi-x-circle-fill text-danger mt-1';
              }

              const content = document.createElement('div');
              content.className = 'small';
              content.innerHTML = '<strong class="text-body">' + f.title + '</strong><div class="text-secondary">' + f.detail + '</div>';

              item.append(icon, content);
              list.appendChild(item);
            });
          }
        })
        .catch(err => {
          alert('Could not complete AI audit: ' + err.message);
          auditModal.hide();
        });
      });
    });

    // Reject Modal Trigger
    document.querySelectorAll('.btn-reject-modal').forEach(btn => {
      btn.addEventListener('click', function() {
        const uuid = this.dataset.uuid;
        const name = this.dataset.name;
        document.getElementById('rejectModalTitle').textContent = 'Reject: ' + name;
        document.getElementById('rejectForm').action = '/admin/moderation/' + uuid + '/reject';
        rejectModal.show();
      });
    });
  })();
</script>
@endpush
@endsection
