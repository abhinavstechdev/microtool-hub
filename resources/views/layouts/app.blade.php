<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'MicroTool Hub — Small Web Tools Community & ChatGPT MCP')</title>
  <meta name="description" content="@yield('meta_description', 'Discover, create, edit, preview, and fork responsive web micro-tools. Powered by Laravel and ChatGPT MCP.')">
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="{{ asset('assets/icon.png') }}">

  <!-- Bootstrap 5.3 & Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Google Fonts: Inter & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      --font-mono: 'JetBrains Mono', Consolas, Monaco, monospace;
      --mcp-cyan: #06b6d4;
      --mcp-blue: #3b82f6;
      --mcp-gradient: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
    }

    body {
      font-family: var(--font-sans);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    code, pre, .font-mono {
      font-family: var(--font-mono);
    }

    .navbar-brand-badge {
      background: var(--mcp-gradient);
      color: white;
      font-weight: 800;
      border-radius: 8px;
      padding: 4px 10px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .hero-gradient {
      background: radial-gradient(circle at top right, rgba(6, 182, 212, 0.15), transparent 40%),
                  radial-gradient(circle at top left, rgba(59, 130, 246, 0.15), transparent 40%);
    }

    .tool-card {
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
      border: 1px solid var(--bs-border-color);
      border-radius: 12px;
      overflow: hidden;
      background: var(--bs-body-bg);
    }

    .tool-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.25);
      border-color: var(--mcp-cyan);
    }

    .btn-gradient {
      background: var(--mcp-gradient);
      color: white;
      border: none;
      font-weight: 600;
      transition: opacity 0.2s;
    }

    .btn-gradient:hover {
      opacity: 0.9;
      color: white;
    }

    .badge-mcp {
      background: rgba(6, 182, 212, 0.15);
      color: var(--mcp-cyan);
      border: 1px solid rgba(6, 182, 212, 0.3);
      font-weight: 600;
    }

    footer {
      margin-top: auto;
      border-top: 1px solid var(--bs-border-color);
      background: var(--bs-tertiary-bg);
    }
  </style>

  @stack('styles')
</head>
<body>

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg border-bottom sticky-top bg-body">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
        <img src="{{ asset('assets/icon.png') }}" alt="Logo" width="32" height="32" class="rounded">
        <span class="fw-bold tracking-tight">MicroTool<span class="text-info">Hub</span></span>
        <span class="badge badge-mcp rounded-pill ms-1 font-mono text-uppercase" style="font-size: 0.65rem;">ChatGPT MCP</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('explore') ? 'active fw-semibold text-info' : '' }}" href="{{ route('explore') }}">
              <i class="bi bi-compass me-1"></i> Explore
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('home') }}#categories">
              <i class="bi bi-grid me-1"></i> Categories
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('home') }}#chatgpt-guide">
              <i class="bi bi-robot me-1"></i> ChatGPT Connect
            </a>
          </li>
        </ul>

        <!-- Search Bar -->
        <form class="d-flex me-lg-3 my-2 my-lg-0" action="{{ route('explore') }}" method="GET">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-body-tertiary border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input class="form-control bg-body-tertiary border-start-0" type="search" name="q" placeholder="Search micro-tools..." value="{{ request('q') }}" aria-label="Search">
          </div>
        </form>

        <!-- Right Side Auth / Theme -->
        <div class="d-flex align-items-center gap-2">
          <!-- Theme Toggle -->
          <button class="btn btn-sm btn-outline-secondary" id="themeToggleBtn" title="Toggle theme">
            <i class="bi bi-sun-fill" id="themeIcon"></i>
          </button>

          @auth
            <a href="{{ route('dashboard.index') }}" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i>
                <span>{{ auth()->user()->name }}</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header">Signed in as <strong>{{ auth()->user()->email }}</strong></h6></li>
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
            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">Sign In</a>
            <a href="{{ route('register') }}" class="btn btn-sm btn-gradient">Get Started</a>
          @endauth
        </div>
      </div>
    </div>
  </nav>

  <!-- Flash Messages -->
  <div class="container mt-3">
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
  </div>

  <!-- Main Content -->
  <main class="flex-grow-1">
    @yield('content')
  </main>

  <!-- Footer -->
  <footer class="py-5 mt-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <img src="{{ asset('assets/icon.png') }}" alt="Logo" width="28" height="28" class="rounded">
            <span class="fw-bold fs-5">MicroTool<span class="text-info">Hub</span></span>
          </div>
          <p class="text-muted small">
            The open community platform for single-purpose web tools. Build, edit, preview, and fork web tools natively or via ChatGPT using the Model Context Protocol (MCP).
          </p>
          <div class="d-flex gap-2 text-muted">
            <span class="badge bg-secondary-subtle text-secondary font-mono">PHP 8.3+</span>
            <span class="badge bg-secondary-subtle text-secondary font-mono">Laravel 13</span>
            <span class="badge bg-info-subtle text-info font-mono">MCP Apps 1.0</span>
          </div>
        </div>
        <div class="col-6 col-lg-2 offset-lg-1">
          <h6 class="fw-bold mb-3">Explore</h6>
          <ul class="list-unstyled text-muted small">
            <li class="mb-2"><a href="{{ route('explore', ['sort' => 'popular']) }}" class="text-decoration-none text-muted">Popular Tools</a></li>
            <li class="mb-2"><a href="{{ route('explore', ['sort' => 'recent']) }}" class="text-decoration-none text-muted">Recently Added</a></li>
            <li class="mb-2"><a href="{{ route('explore', ['category' => 'developer-tools']) }}" class="text-decoration-none text-muted">Developer Utilities</a></li>
            <li class="mb-2"><a href="{{ route('explore', ['category' => 'design-colors']) }}" class="text-decoration-none text-muted">CSS & Design</a></li>
          </ul>
        </div>
        <div class="col-6 col-lg-2">
          <h6 class="fw-bold mb-3">AI Platform</h6>
          <ul class="list-unstyled text-muted small">
            <li class="mb-2"><a href="{{ url('/.well-known/oauth-protected-resource') }}" class="text-decoration-none text-muted">RFC 9728 Discovery</a></li>
            <li class="mb-2"><a href="{{ url('/.well-known/oauth-authorization-server') }}" class="text-decoration-none text-muted">OAuth 2.1 Metadata</a></li>
            <li class="mb-2"><a href="{{ url('/plugin.json') }}" class="text-decoration-none text-muted">Plugin Manifest</a></li>
            <li class="mb-2"><a href="{{ url('/mcp.json') }}" class="text-decoration-none text-muted">MCP Config</a></li>
          </ul>
        </div>
        <div class="col-lg-3">
          <h6 class="fw-bold mb-3">ChatGPT Integration</h6>
          <p class="text-muted small">
            Connect ChatGPT directly to your workspace using Streamable HTTP at <code>{{ url('/mcp') }}</code>.
          </p>
          <a href="{{ route('dashboard.tokens') }}" class="btn btn-sm btn-outline-info">
            <i class="bi bi-cpu me-1"></i> Setup MCP Connection
          </a>
        </div>
      </div>
      <div class="border-top mt-4 pt-4 d-flex flex-wrap justify-content-between align-items-center text-muted small">
        <div>&copy; {{ date('Y') }} MicroTool Hub. Released under MIT License.</div>
        <div class="d-flex gap-3">
          <span>Privacy Policy</span>
          <span>Terms of Service</span>
          <span>Security & Limits</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Theme Toggle JS -->
  <script>
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');
    const htmlEl = document.documentElement;

    function setTheme(theme) {
      htmlEl.setAttribute('data-bs-theme', theme);
      localStorage.setItem('theme', theme);
      if (theme === 'dark') {
        themeIcon.className = 'bi bi-sun-fill';
      } else {
        themeIcon.className = 'bi bi-moon-fill';
      }
    }

    const savedTheme = localStorage.getItem('theme') || 'dark';
    setTheme(savedTheme);

    themeToggleBtn.addEventListener('click', () => {
      const current = htmlEl.getAttribute('data-bs-theme');
      setTheme(current === 'dark' ? 'light' : 'dark');
    });
  </script>

  @stack('scripts')
</body>
</html>
