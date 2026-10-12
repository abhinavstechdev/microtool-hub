# cPanel Deployment & Production Guide — MicroTool Hub

This guide walks through deploying **MicroTool Hub** to any standard cPanel shared hosting environment running PHP 8.3+ and MySQL 8.

---

## 1. Hosting Prerequisites
- **PHP Version**: PHP 8.3+ (PHP 8.3, 8.4, or 8.5) enabled via cPanel *MultiPHP Manager*.
- **PHP Extensions**: `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `curl`, `dom`, `zip`, `gd`, `bcmath`.
- **Database**: MySQL 8.0+ or MariaDB 10.5+ created via cPanel *MySQL Database Wizard*.
- **SSL / HTTPS**: Active SSL certificate (AutoSSL or Let's Encrypt). Required for ChatGPT remote MCP and OAuth 2.1 endpoints.
- **Node.js**: **NOT REQUIRED** on the server. All CSS/JS assets use CDN or pre-compiled assets.

---

## 2. Directory Layout on cPanel

To ensure absolute security, keep the Laravel application files and private workspace files outside the public web root:

```text
/home/username/
├── microtool_app/                 <-- All Laravel project files (outside web root)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   │   └── app/
│   │       └── private/           <-- Private workspace files & snapshots
│   │           ├── projects/
│   │           └── snapshots/
│   ├── vendor/
│   └── .env                       <-- Production environment secrets
│
└── public_html/                   <-- Public web root (Contents of Laravel's 'public/')
    ├── index.php                  <-- Entrypoint pointing to ../microtool_app/
    ├── .htaccess                  <-- Apache rewrite rules & auth headers
    ├── assets/                    <-- Icons and public images
    └── mcp/                       <-- Widget templates
```

---

## 3. Step-by-Step Deployment Procedure

### Step 1: Upload Application Files
1. In cPanel **File Manager**, create a folder `/home/username/microtool_app`.
2. Upload the project files (including `vendor/`) to `/home/username/microtool_app`.
3. Move the contents of `microtool_app/public/` directly into `/home/username/public_html/`.

### Step 2: Update Entrypoint `public_html/index.php`
Edit `/home/username/public_html/index.php` to adjust paths:

```php
// Change lines:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// To point to your microtool_app directory:
require __DIR__.'/../microtool_app/vendor/autoload.php';
$app = require_once __DIR__.'/../microtool_app/bootstrap/app.php';
```

### Step 3: Configure `.env` in `microtool_app/`
Copy `.env.example` to `.env` inside `/home/username/microtool_app/`:

```env
APP_NAME="MicroTool Hub"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=username_microtool
DB_USERNAME=username_microtooluser
DB_PASSWORD=your_strong_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

OPENAI_APPS_CHALLENGE_TOKEN=your_unique_openai_challenge_token
```

### Step 4: Run Database Migrations
**If SSH / Terminal is available in cPanel:**
```bash
cd ~/microtool_app
php artisan migrate --force
php artisan db:seed --force
```

**If SSH is NOT available:**
Import the database schema and initial seed data using cPanel **phpMyAdmin** or run a one-time migration controller.

### Step 5: Verify Permissions
Ensure the following directories have write permissions (chmod `755` for directories, `644` for files):
- `~/microtool_app/storage/`
- `~/microtool_app/storage/app/private/`
- `~/microtool_app/bootstrap/cache/`

---

## 4. Apache `.htaccess` Header Handling

In `/home/username/public_html/.htaccess`, verify that the `Authorization` header is passed through to PHP (required for MCP Bearer tokens):

```apache
RewriteEngine On
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

---

## 5. Connecting ChatGPT & Codex to Your Deployed MCP Server

### Method A: Streamable HTTP Connector in ChatGPT
1. Go to your MicroTool Hub Dashboard at `https://yourdomain.com/dashboard/tokens`.
2. Generate a Personal MCP Access Token.
3. In ChatGPT custom actions / developer settings, configure:
   - **Endpoint URL**: `https://yourdomain.com/mcp`
   - **Authentication**: Bearer Token
   - **Token**: `mcp_your_generated_token_here`

### Method B: Agent Plugins & OAuth 2.1
ChatGPT automatically discovers your server endpoints via RFC 9728:
- Resource Metadata: `GET https://yourdomain.com/.well-known/oauth-protected-resource`
- Server Metadata: `GET https://yourdomain.com/.well-known/oauth-authorization-server`
- Verification Challenge: `GET https://yourdomain.com/.well-known/openai-apps-challenge`

---

## 6. Production Health Check Checklist
- [ ] Visit `https://yourdomain.com/` — Homepage loads with theme toggle.
- [ ] Visit `https://yourdomain.com/explore` — Micro-tools catalog renders.
- [ ] Visit `https://yourdomain.com/.well-known/oauth-protected-resource` — Returns HTTP 200 with JSON resource metadata.
- [ ] Visit `https://yourdomain.com/.well-known/openai-apps-challenge` — Returns verification token.
- [ ] Run `python scripts/validate_plugin.py .` locally to confirm all OpenAI requirements are met.
