<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'MicroTool Hub — Small Tools. Big Productivity.')</title>
  <meta name="description" content="@yield('meta_description', 'Free online tools to convert, edit, calculate, and create — all in one place. Powered by Laravel and ChatGPT MCP.')">
  
  <!-- Anti-flicker theme initialization -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('microtool_theme') || 'dark';
      document.documentElement.setAttribute('data-bs-theme', savedTheme);
    })();
  </script>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="{{ asset('assets/icon.png') }}">

  <!-- Bootstrap 5.3 & Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;850&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- MicroTool Hub Design System -->
  <link rel="stylesheet" href="{{ asset('assets/design-system.css') }}?v=2026.1">

  @stack('styles')
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="site-navbar navbar navbar-expand-lg sticky-top">
    <div class="container">
      <!-- Brand Logo -->
      <a class="brand-badge" href="{{ route('home') }}">
        <div class="brand-icon">
          <i class="bi bi-cpu-fill fs-5"></i>
        </div>
        <span class="d-flex align-items-center gap-2">
          <span>MicroTool<span style="color: var(--violet-500);">Hub</span></span>
          <span class="mcp-pill d-none d-sm-inline-block">MCP</span>
        </span>
      </a>

      <!-- Mobile Toggler -->
      <button class="navbar-toggler border-0 p-2 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list fs-3 text-secondary"></i>
      </button>

      <!-- Nav Links -->
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-1">
          <li class="nav-item">
            <a class="nav-link-custom {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
              <i class="bi bi-house-door"></i> Home
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link-custom {{ request()->routeIs('explore') ? 'active' : '' }}" href="{{ route('explore') }}">
              <i class="bi bi-grid-3x3-gap"></i> All Tools
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link-custom" href="{{ route('home') }}#categories">
              <i class="bi bi-tags"></i> Categories
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link-custom" href="{{ route('home') }}#about">
              <i class="bi bi-info-circle"></i> About
            </a>
          </li>
        </ul>

        <!-- Search Bar (Desktop & Tablet) -->
        <form class="d-none d-md-flex me-lg-3 my-2 my-lg-0" action="{{ route('explore') }}" method="GET" style="max-width: 240px;">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-transparent border-end-0 border-secondary-subtle">
              <i class="bi bi-search text-muted small"></i>
            </span>
            <input class="form-control bg-transparent border-start-0 border-secondary-subtle text-body shadow-none" type="search" name="q" placeholder="Find a tool..." value="{{ request('q') }}" aria-label="Search">
          </div>
        </form>

        <!-- Right Side: Theme Toggle & Authentication -->
        <div class="d-flex align-items-center gap-2 pt-2 pt-lg-0">
          <!-- Theme Toggle Button -->
          <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="Switch Theme (Dark / Light)" aria-label="Switch Theme">
            <i class="bi bi-sun-fill" id="themeIcon"></i>
          </button>

          @auth
            @if(auth()->user()->role === 'admin')
              @php
                $pendingQueueCount = \App\Models\Project::where('status', 'pending_approval')->count();
              @endphp
              <a href="{{ route('admin.moderation') }}" class="btn-outline-violet d-inline-flex align-items-center gap-1 text-decoration-none py-1 px-3 small" style="border-color: #8b5cf6; color: #a78bfa;">
                <i class="bi bi-shield-check"></i>
                <span class="d-none d-sm-inline">Moderation</span>
                @if($pendingQueueCount > 0)
                  <span class="badge rounded-pill bg-danger ms-1 font-mono small">{{ $pendingQueueCount }}</span>
                @endif
              </a>
            @endif
            <a href="{{ route('dashboard.index') }}" class="btn-secondary-custom d-inline-flex align-items-center gap-1 text-decoration-none py-1 px-3 small">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <div class="dropdown">
              <button class="btn-secondary-custom dropdown-toggle d-flex align-items-center gap-2 py-1 px-3 small" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                @if(auth()->user()->role === 'admin')
                  <span class="badge bg-danger text-white font-mono" style="font-size: 0.6rem; padding: 2px 4px;">ADMIN</span>
                @endif
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow border-subtle">
                <li><h6 class="dropdown-header">Signed in as <strong>{{ auth()->user()->email }}</strong></h6></li>
                @if(auth()->user()->role === 'admin')
                  <li>
                    <a class="dropdown-item text-warning fw-semibold" href="{{ route('admin.moderation') }}">
                      <i class="bi bi-shield-lock me-2"></i>Admin Moderation Portal
                      @if($pendingQueueCount > 0)
                        <span class="badge rounded-pill bg-danger ms-1">{{ $pendingQueueCount }}</span>
                      @endif
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                @endif
                <li><a class="dropdown-item" href="{{ route('dashboard.index') }}"><i class="bi bi-kanban me-2"></i>My Projects</a></li>
                <li><a class="dropdown-item" href="{{ route('dashboard.tokens') }}"><i class="bi bi-key me-2"></i>MCP Access Tokens</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                  </form>
                </li>
              </ul>
            </div>
          @else
            <a href="{{ route('login') }}" class="btn-secondary-custom text-decoration-none py-1 px-3 small">Sign In</a>
            <a href="{{ route('register') }}" class="btn-violet text-decoration-none py-1 px-3 small">Get Started</a>
          @endauth
        </div>
      </div>
    </div>
  </nav>

  <!-- Flash Messages -->
  <div class="container mt-3">
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 rounded-3 border-0 shadow-sm" role="alert" style="background: var(--color-success-bg); color: var(--color-success); border: 1px solid var(--color-success) !important;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div class="fw-medium">{{ session('success') }}</div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 rounded-3 border-0 shadow-sm" role="alert" style="background: var(--color-error-bg); color: var(--color-error); border: 1px solid var(--color-error) !important;">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div class="fw-medium">{{ session('error') }}</div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
      </div>
    @endif
  </div>

  <!-- Main Content -->
  <main class="flex-grow-1">
    @yield('content')
  </main>

  <!-- Professional Footer -->
  <footer class="mt-auto border-top py-5" style="background: var(--footer-bg); border-color: var(--border-subtle) !important;">
    <div class="container">
      <div class="row g-4 justify-content-between">
        <!-- Col 1: Brand Info -->
        <div class="col-lg-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="brand-icon" style="width: 28px; height: 28px; font-size: 0.9rem;">
              <i class="bi bi-cpu-fill"></i>
            </div>
            <span class="fw-bold fs-5 text-body">MicroTool<span style="color: var(--violet-500);">Hub</span></span>
          </div>
          <p class="text-secondary small mb-3">
            Small tools. Big productivity. Free, single-purpose web tools processed locally in your browser. Build, edit, preview, and fork natively or connect to ChatGPT via MCP.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <span class="badge font-mono small py-1 px-2" style="background: var(--violet-soft); color: var(--violet-500); border: 1px solid var(--violet-border);">Client-Side Isolated</span>
            <span class="badge font-mono small py-1 px-2" style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);">100% Private</span>
            <span class="badge font-mono small py-1 px-2 text-secondary bg-body-tertiary border">Model Context Protocol</span>
          </div>
        </div>

        <!-- Col 2: Directory -->
        <div class="col-6 col-lg-2">
          <h6 class="fw-bold mb-3 text-body">Directory</h6>
          <ul class="list-unstyled text-secondary small">
            <li class="mb-2"><a href="{{ route('explore', ['sort' => 'popular']) }}" class="text-decoration-none text-secondary">Popular Tools</a></li>
            <li class="mb-2"><a href="{{ route('explore', ['sort' => 'recent']) }}" class="text-decoration-none text-secondary">Recently Added</a></li>
            <li class="mb-2"><a href="{{ route('explore', ['category' => 'developer-tools']) }}" class="text-decoration-none text-secondary">Developer Tools</a></li>
            <li class="mb-2"><a href="{{ route('explore', ['category' => 'design-colors']) }}" class="text-decoration-none text-secondary">Design & Colors</a></li>
            <li class="mb-2"><a href="{{ route('explore', ['category' => 'converters']) }}" class="text-decoration-none text-secondary">Converters</a></li>
          </ul>
        </div>

        <!-- Col 3: AI Platform -->
        <div class="col-6 col-lg-2">
          <h6 class="fw-bold mb-3 text-body">AI Platform</h6>
          <ul class="list-unstyled text-secondary small">
            <li class="mb-2"><a href="{{ url('/.well-known/oauth-protected-resource') }}" class="text-decoration-none text-secondary">RFC 9728 Discovery</a></li>
            <li class="mb-2"><a href="{{ url('/.well-known/oauth-authorization-server') }}" class="text-decoration-none text-secondary">OAuth 2.1 Metadata</a></li>
            <li class="mb-2"><a href="{{ url('/plugin.json') }}" class="text-decoration-none text-secondary">Plugin Manifest</a></li>
            <li class="mb-2"><a href="{{ url('/mcp.json') }}" class="text-decoration-none text-secondary">MCP Schema</a></li>
            <li class="mb-2"><a href="{{ route('home') }}#chatgpt-connect" class="text-decoration-none text-secondary">Connect ChatGPT</a></li>
          </ul>
        </div>

        <!-- Col 4: MCP Connect -->
        <div class="col-lg-3">
          <h6 class="fw-bold mb-3 text-body">ChatGPT Integration</h6>
          <p class="text-secondary small mb-3">
            Connect ChatGPT directly to your workspace using Streamable HTTP:
            <code class="d-block mt-1 p-2 rounded small text-break" style="background: var(--bg-card); color: var(--violet-500); border: 1px solid var(--border-subtle);">{{ url('/mcp') }}</code>
          </p>
          <a href="{{ route('dashboard.tokens') }}" class="btn-outline-violet d-inline-flex align-items-center gap-1 text-decoration-none py-1 px-3 small">
            <i class="bi bi-cpu"></i> Setup Connector
          </a>
        </div>
      </div>

      <!-- Copyright bar -->
      <div class="border-top mt-4 pt-4 d-flex flex-wrap justify-content-between align-items-center text-secondary small" style="border-color: var(--border-subtle) !important;">
        <div>&copy; {{ date('Y') }} MicroTool Hub. Open source micro-tool platform.</div>
        <div class="d-flex gap-3">
          <a href="{{ route('explore') }}" class="text-secondary text-decoration-none">Explore Tools</a>
          <a href="{{ route('home') }}#categories" class="text-secondary text-decoration-none">Categories</a>
          <a href="{{ route('home') }}#about" class="text-secondary text-decoration-none">About</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Theme Toggle Controller -->
  <script>
    (function() {
      const themeToggleBtn = document.getElementById('themeToggleBtn');
      const themeIcon = document.getElementById('themeIcon');
      const htmlEl = document.documentElement;

      function updateIcon(theme) {
        if (themeIcon) {
          if (theme === 'dark') {
            themeIcon.className = 'bi bi-sun-fill text-warning';
          } else {
            themeIcon.className = 'bi bi-moon-stars-fill text-primary';
          }
        }
      }

      function applyTheme(theme) {
        htmlEl.setAttribute('data-bs-theme', theme);
        localStorage.setItem('microtool_theme', theme);
        updateIcon(theme);
      }

      const initialTheme = localStorage.getItem('microtool_theme') || 'dark';
      updateIcon(initialTheme);

      if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
          const currentTheme = htmlEl.getAttribute('data-bs-theme') || 'dark';
          const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
          applyTheme(newTheme);
        });
      }
    })();
  </script>

  @stack('scripts')
</body>
</html>
