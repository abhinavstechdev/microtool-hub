<?php

namespace Database\Seeders;

use App\Models\OAuthClient;
use App\Models\Project;
use App\Models\ToolCategory;
use App\Models\User;
use App\Services\ProjectStorageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $storage = new ProjectStorageService();

        // 1. Create Tool Categories
        $categories = [
            ['name' => 'Developer Tools', 'slug' => 'developer-tools', 'icon' => 'bi-code-slash', 'description' => 'Formatters, parsers, linters, and developer utilities.', 'display_order' => 1],
            ['name' => 'Design & Colors', 'slug' => 'design-colors', 'icon' => 'bi-palette', 'description' => 'Color pickers, gradient generators, CSS tools, and shadows.', 'display_order' => 2],
            ['name' => 'Converters', 'slug' => 'converters', 'icon' => 'bi-arrow-left-right', 'description' => 'Unit, timestamp, base64, and format converters.', 'display_order' => 3],
            ['name' => 'Calculators', 'slug' => 'calculators', 'icon' => 'bi-calculator', 'description' => 'Financial, math, mortgage, and health calculators.', 'display_order' => 4],
            ['name' => 'Text & Content', 'slug' => 'text-content', 'icon' => 'bi-file-text', 'description' => 'Word counters, markdown editors, diff checkers, and text cases.', 'display_order' => 5],
            ['name' => 'Utilities', 'slug' => 'utilities', 'icon' => 'bi-gear-wide-connected', 'description' => 'General purpose web tools and productivity widgets.', 'display_order' => 6],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['slug']] = ToolCategory::create($cat);
        }

        // 2. Create Users
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@microtoolhub.com',
            'password' => Hash::make('adminpassword123'),
            'role' => 'admin',
            'bio' => 'System administrator for MicroTool Hub.',
            'storage_quota_bytes' => 52428800, // 50MB
        ]);

        $creator = User::create([
            'name' => 'Alex Rivera',
            'username' => 'alexdev',
            'email' => 'alex@microtoolhub.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'bio' => 'Web developer passionate about fast, accessible micro-tools.',
            'storage_quota_bytes' => 20971520, // 20MB
        ]);

        // 3. Create Default OAuth Client for ChatGPT
        OAuthClient::create([
            'user_id' => $admin->id,
            'client_id' => 'chatgpt_client_microtool',
            'client_secret' => 'chatgpt_secret_microtool_2026',
            'name' => 'ChatGPT Official Plugin Connector',
            'redirect_uris' => "https://chatgpt.com/aip/plugin-oauth/callback\nhttps://chat.openai.com/aip/plugin-oauth/callback\nhttp://localhost:8000/oauth/callback",
            'is_confidential' => true,
            'revoked' => false,
        ]);

        // 4. Create Sample Tool: JSON Formatter & Validator
        $p1 = Project::create([
            'user_id' => $creator->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'JSON Formatter & Validator',
            'slug' => 'json-formatter',
            'description' => 'Prettify, minify, validate, and download clean JSON with real-time error line reporting.',
            'category_id' => $categoryModels['developer-tools']->id,
            'visibility' => 'public',
            'status' => 'published',
            'current_version' => 'v1.0.0',
            'likes_count' => 14,
            'views_count' => 128,
            'forks_count' => 3,
            'is_featured' => true,
            'published_at' => now(),
        ]);

        $jsonHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JSON Formatter & Validator</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <header>
      <h1>{ } JSON Formatter & Validator</h1>
      <p class="subtitle">Format, validate, minify, and inspect JSON in real-time.</p>
    </header>

    <div class="toolbar">
      <button id="formatBtn" class="btn btn-primary">Format (2 Spaces)</button>
      <button id="format4Btn" class="btn btn-secondary">Format (4 Spaces)</button>
      <button id="minifyBtn" class="btn btn-secondary">Minify</button>
      <button id="copyBtn" class="btn btn-outline">Copy Output</button>
      <button id="clearBtn" class="btn btn-danger">Clear</button>
      <button id="sampleBtn" class="btn btn-ghost">Load Sample</button>
    </div>

    <div id="statusAlert" class="status-alert hidden"></div>

    <main class="editor-grid">
      <div class="panel">
        <div class="panel-header">
          <span>Input JSON</span>
          <span id="inputStats" class="stats-text">0 chars</span>
        </div>
        <textarea id="jsonInput" placeholder="Paste raw JSON here..."></textarea>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span>Formatted Output</span>
          <span id="outputStats" class="stats-text">0 chars</span>
        </div>
        <textarea id="jsonOutput" readonly placeholder="Formatted output will appear here..."></textarea>
      </div>
    </main>
  </div>
  <script src="app.js"></script>
</body>
</html>
HTML;

        $jsonCss = <<<'CSS'
:root {
  --bg: #0f172a;
  --surface: #1e293b;
  --border: #334155;
  --text: #f8fafc;
  --text-muted: #94a3b8;
  --primary: #3b82f6;
  --primary-hover: #2563eb;
  --danger: #ef4444;
  --success: #10b981;
}
* { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
body { background: var(--bg); color: var(--text); padding: 20px; min-height: 100vh; }
.container { max-width: 1200px; margin: 0 auto; }
header { margin-bottom: 20px; text-align: center; }
header h1 { font-size: 1.8rem; font-weight: 700; color: #60a5fa; }
.subtitle { color: var(--text-muted); font-size: 0.95rem; margin-top: 4px; }
.toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; justify-content: center; }
.btn { padding: 8px 14px; border-radius: 6px; font-weight: 500; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; transition: all 0.15s ease; }
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: var(--primary-hover); }
.btn-secondary { background: #334155; color: white; }
.btn-secondary:hover { background: #475569; }
.btn-outline { background: transparent; border-color: var(--border); color: var(--text); }
.btn-outline:hover { background: var(--surface); }
.btn-danger { background: transparent; border-color: var(--danger); color: var(--danger); }
.btn-danger:hover { background: var(--danger); color: white; }
.btn-ghost { background: transparent; color: var(--text-muted); }
.btn-ghost:hover { color: var(--text); }
.status-alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 0.9rem; }
.status-alert.success { background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; }
.status-alert.error { background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; }
.hidden { display: none; }
.editor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 768px) { .editor-grid { grid-template-columns: 1fr; } }
.panel { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; }
.panel-header { display: flex; justify-content: space-between; padding: 10px 14px; background: #162032; border-bottom: 1px solid var(--border); font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
textarea { width: 100%; height: 420px; background: transparent; border: none; color: #e2e8f0; font-family: "JetBrains Mono", Consolas, Monaco, monospace; font-size: 0.9rem; padding: 14px; resize: vertical; line-height: 1.5; outline: none; }
textarea:focus { background: rgba(255,255,255,0.01); }
CSS;

        $jsonJs = <<<'JS'
const input = document.getElementById('jsonInput');
const output = document.getElementById('jsonOutput');
const alertBox = document.getElementById('statusAlert');
const inputStats = document.getElementById('inputStats');
const outputStats = document.getElementById('outputStats');

function showAlert(message, type) {
  alertBox.className = `status-alert ${type}`;
  alertBox.textContent = message;
  alertBox.classList.remove('hidden');
}

function clearAlert() {
  alertBox.className = 'status-alert hidden';
}

function updateStats() {
  inputStats.textContent = `${input.value.length} chars`;
  outputStats.textContent = `${output.value.length} chars`;
}

function formatJson(spaces = 2) {
  clearAlert();
  const val = input.value.trim();
  if (!val) {
    showAlert('Please enter JSON text to format.', 'error');
    return;
  }
  try {
    const parsed = JSON.parse(val);
    output.value = JSON.stringify(parsed, null, spaces);
    showAlert('JSON is valid and formatted successfully.', 'success');
  } catch (err) {
    showAlert(`JSON Error: ${err.message}`, 'error');
  }
  updateStats();
}

function minifyJson() {
  clearAlert();
  const val = input.value.trim();
  if (!val) {
    showAlert('Please enter JSON text to minify.', 'error');
    return;
  }
  try {
    const parsed = JSON.parse(val);
    output.value = JSON.stringify(parsed);
    showAlert('JSON minified successfully.', 'success');
  } catch (err) {
    showAlert(`JSON Error: ${err.message}`, 'error');
  }
  updateStats();
}

document.getElementById('formatBtn').addEventListener('click', () => formatJson(2));
document.getElementById('format4Btn').addEventListener('click', () => formatJson(4));
document.getElementById('minifyBtn').addEventListener('click', minifyJson);

document.getElementById('copyBtn').addEventListener('click', () => {
  if (!output.value) return;
  navigator.clipboard.writeText(output.value).then(() => {
    showAlert('Formatted JSON copied to clipboard!', 'success');
  });
});

document.getElementById('clearBtn').addEventListener('click', () => {
  input.value = '';
  output.value = '';
  clearAlert();
  updateStats();
});

document.getElementById('sampleBtn').addEventListener('click', () => {
  const sample = {
    app: "MicroTool Hub",
    version: "1.0.0",
    features: ["MCP Server", "In-Chat Previews", "Community Hub"],
    metrics: { uptime: 99.98, activeUsers: 1420 },
    verified: true
  };
  input.value = JSON.stringify(sample);
  formatJson(2);
});

input.addEventListener('input', updateStats);
JS;

        $storage->writeFile($p1, 'index.html', $jsonHtml);
        $storage->writeFile($p1, 'style.css', $jsonCss);
        $storage->writeFile($p1, 'app.js', $jsonJs);
        $storage->createSnapshot($p1, 'v1.0.0', 'Initial release');

        // 5. Create Sample Tool: CSS Box-Shadow Generator
        $p2 = Project::create([
            'user_id' => $creator->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'CSS Box-Shadow Generator',
            'slug' => 'box-shadow-generator',
            'description' => 'Interactive slider controls to design, fine-tune, and copy CSS box-shadow code.',
            'category_id' => $categoryModels['design-colors']->id,
            'visibility' => 'public',
            'status' => 'published',
            'current_version' => 'v1.0.0',
            'likes_count' => 9,
            'views_count' => 84,
            'forks_count' => 1,
            'is_featured' => true,
            'published_at' => now(),
        ]);

        $shadowHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CSS Box-Shadow Generator</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="card">
    <h2>CSS Box-Shadow Generator</h2>
    <div class="preview-box" id="box">Preview Box</div>
    <div class="controls">
      <label>X Offset: <span id="xVal">0px</span><input type="range" id="x" min="-50" max="50" value="0"></label>
      <label>Y Offset: <span id="yVal">10px</span><input type="range" id="y" min="-50" max="50" value="10"></label>
      <label>Blur: <span id="blurVal">25px</span><input type="range" id="blur" min="0" max="100" value="25"></label>
      <label>Spread: <span id="spreadVal">0px</span><input type="range" id="spread" min="-30" max="30" value="0"></label>
      <label>Color: <input type="color" id="color" value="#000000"></label>
      <label>Opacity: <span id="opacityVal">0.3</span><input type="range" id="opacity" min="0" max="1" step="0.05" value="0.3"></label>
    </div>
    <div class="output">
      <code id="code">box-shadow: 0px 10px 25px 0px rgba(0, 0, 0, 0.3);</code>
      <button id="copyBtn">Copy CSS</button>
    </div>
  </div>
  <script src="app.js"></script>
</body>
</html>
HTML;

        $shadowCss = <<<'CSS'
body {
  background: #0f172a;
  color: #f8fafc;
  font-family: sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
  padding: 16px;
}
.card {
  background: #1e293b;
  border-radius: 12px;
  padding: 24px;
  max-width: 600px;
  width: 100%;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}
h2 { margin-top: 0; color: #38bdf8; text-align: center; font-size: 1.5rem; }
.preview-box {
  width: 180px;
  height: 120px;
  background: #38bdf8;
  color: #0f172a;
  font-weight: bold;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 30px auto;
  transition: box-shadow 0.1s ease;
}
.controls { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
label { display: flex; flex-direction: column; font-size: 0.85rem; color: #94a3b8; }
input[type="range"] { margin-top: 6px; }
.output {
  display: flex;
  align-items: center;
  background: #090d16;
  padding: 12px;
  border-radius: 6px;
  gap: 12px;
}
code { flex: 1; font-family: monospace; color: #4ade80; font-size: 0.85rem; overflow-x: auto; }
button {
  background: #38bdf8;
  border: none;
  padding: 8px 14px;
  border-radius: 4px;
  font-weight: bold;
  cursor: pointer;
}
button:hover { background: #0284c7; color: white; }
CSS;

        $shadowJs = <<<'JS'
const x = document.getElementById('x');
const y = document.getElementById('y');
const blur = document.getElementById('blur');
const spread = document.getElementById('spread');
const color = document.getElementById('color');
const opacity = document.getElementById('opacity');
const box = document.getElementById('box');
const code = document.getElementById('code');

function hexToRgba(hex, alpha) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function updateShadow() {
  document.getElementById('xVal').textContent = `${x.value}px`;
  document.getElementById('yVal').textContent = `${y.value}px`;
  document.getElementById('blurVal').textContent = `${blur.value}px`;
  document.getElementById('spreadVal').textContent = `${spread.value}px`;
  document.getElementById('opacityVal').textContent = opacity.value;

  const rgba = hexToRgba(color.value, opacity.value);
  const shadow = `${x.value}px ${y.value}px ${blur.value}px ${spread.value}px ${rgba}`;
  box.style.boxShadow = shadow;
  code.textContent = `box-shadow: ${shadow};`;
}

[x, y, blur, spread, color, opacity].forEach(el => el.addEventListener('input', updateShadow));
updateShadow();

document.getElementById('copyBtn').addEventListener('click', () => {
  navigator.clipboard.writeText(code.textContent).then(() => {
    const btn = document.getElementById('copyBtn');
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy CSS', 1500);
  });
});
JS;

        $storage->writeFile($p2, 'index.html', $shadowHtml);
        $storage->writeFile($p2, 'style.css', $shadowCss);
        $storage->writeFile($p2, 'app.js', $shadowJs);
        $storage->createSnapshot($p2, 'v1.0.0', 'Initial release');
    }
}
