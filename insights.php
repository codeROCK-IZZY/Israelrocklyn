<?php
/**
 * The private insights dashboard (/insights): portfolio visits and GitHub traffic, for the owner only.
 * Not linked from anywhere, never indexed, and every response requires a signed-in session.
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$signedIn = auth_signed_in();
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if (!auth_csrf_ok($_POST['csrf'] ?? null)) {
        $message = 'The form expired. Please try again.';
    } elseif ($action === 'login') {
        if (auth_locked()) {
            $message = 'Too many attempts. Try again in 15 minutes.';
        } elseif (auth_attempt((string) ($_POST['password'] ?? ''))) {
            header('Location: /insights', true, 303);
            exit;
        } else {
            $message = 'That password is not right.';
        }
    } elseif ($signedIn && $action === 'logout') {
        auth_sign_out();
        header('Location: /insights', true, 303);
        exit;
    } elseif ($signedIn && $action === 'token') {
        $token = trim((string) ($_POST['token'] ?? ''));
        if ($token !== '' && !preg_match('/^(github_pat_|ghp_|gho_)[A-Za-z0-9_]{20,255}$/', $token)) {
            $message = 'That does not look like a GitHub token.';
        } else {
            analytics_save_settings(['github_token' => $token]);
            $h = ght_collect(true);
            $message = $token === '' ? 'Token removed.' : (($h['error'] ?? '') !== '' ? $h['error'] : 'Token saved; GitHub traffic collected.');
        }
    } elseif ($signedIn && $action === 'password') {
        $new = (string) ($_POST['new'] ?? '');
        $hash = (string) (analytics_settings()['password_hash'] ?? '');
        if (!password_verify((string) ($_POST['current'] ?? ''), $hash)) {
            $message = 'The current password is not right.';
        } elseif (strlen($new) < 12) {
            $message = 'Use at least 12 characters.';
        } else {
            analytics_save_settings(['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
            $message = 'Password changed.';
        }
    }
    $signedIn = auth_signed_in();
}

if ($signedIn && isset($_GET['data'])) {
    auth_private_headers('application/json; charset=utf-8');
    $days = max(7, min(400, (int) ($_GET['days'] ?? 90)));
    $gh = ght_collect(isset($_GET['refresh']));
    $token = ght_token();
    echo json_encode([
        'site' => analytics_series($days),
        'github' => ['updated' => $gh['updated'] ?? 0, 'error' => $gh['error'] ?? '', 'repos' => $gh['repos'] ?? []],
        'token' => $token === '' ? '' : substr($token, 0, 11) . '…' . substr($token, -4),
        'now' => gmdate('Y-m-d'),
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

auth_private_headers();
$csrf = auth_csrf();
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Insights</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(asset('css/insights.css')) ?>">
<?php if ($signedIn): ?>
  <script src="<?= e(asset('js/insights.js')) ?>" defer></script>
<?php endif; ?>
</head>
<body>
<?php if (!$signedIn): ?>
  <main class="login">
    <form method="post" action="/insights" class="card">
      <h1>Insights</h1>
      <p class="muted">Private. Sign in to continue.</p>
      <input type="hidden" name="action" value="login">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <label>Password <input type="password" name="password" autocomplete="current-password" required autofocus></label>
      <button type="submit">Sign in</button>
<?php if ($message !== ''): ?>
      <p class="msg" role="alert"><?= e($message) ?></p>
<?php endif; ?>
    </form>
  </main>
<?php else: ?>
  <header class="bar">
    <h1>Insights</h1>
    <nav class="tabs" role="tablist">
      <button role="tab" data-tab="site" aria-selected="true">Portfolio</button>
      <button role="tab" data-tab="github" aria-selected="false">GitHub</button>
      <button role="tab" data-tab="settings" aria-selected="false">Settings</button>
    </nav>
    <div class="filters">
      <label>Range <select id="range">
        <option value="7">7 days</option><option value="30" selected>30 days</option>
        <option value="90">90 days</option><option value="365">12 months</option>
      </select></label>
      <div class="seg" role="radiogroup" aria-label="Group by">
        <button data-group="day" aria-checked="true" role="radio">Daily</button>
        <button data-group="week" aria-checked="false" role="radio">Weekly</button>
        <button data-group="month" aria-checked="false" role="radio">Monthly</button>
      </div>
      <button id="refresh" class="ghost" title="Fetch GitHub traffic now">Refresh</button>
    </div>
  </header>
<?php if ($message !== ''): ?>
  <p class="msg banner" role="status"><?= e($message) ?></p>
<?php endif; ?>
  <main id="app" data-csrf="<?= e($csrf) ?>">
    <section data-panel="site">
      <div class="tiles" id="site-tiles"></div>
      <div class="card"><h2>Visits over time</h2><div class="legend" id="site-legend"></div><div class="chart" id="site-chart"></div></div>
      <div class="grid3">
        <div class="card"><h2>Top pages</h2><div id="site-pages"></div></div>
        <div class="card"><h2>Where visitors come from</h2><div id="site-refs"></div></div>
        <div class="card"><h2>Clicks</h2><div id="site-clicks"></div></div>
      </div>
      <div class="grid3">
        <div class="card"><h2>Devices</h2><div id="site-devices"></div></div>
        <div class="card"><h2>Browsers</h2><div id="site-browsers"></div></div>
        <div class="card"><h2>Regions (from browser language)</h2><div id="site-regions"></div></div>
      </div>
      <details class="card"><summary>Table view</summary><div id="site-table"></div></details>
    </section>
    <section data-panel="github" hidden>
      <p class="msg" id="gh-note" hidden></p>
      <div class="tiles" id="gh-tiles"></div>
      <div class="card">
        <h2>Repository traffic over time</h2>
        <div class="filters inline">
          <label>Repository <select id="gh-repo"><option value="">All repositories</option></select></label>
          <div class="seg" role="radiogroup" aria-label="Measure">
            <button data-measure="views" aria-checked="true" role="radio">Views</button>
            <button data-measure="clones" aria-checked="false" role="radio">Clones</button>
          </div>
        </div>
        <div class="legend" id="gh-legend"></div><div class="chart" id="gh-chart"></div>
      </div>
      <div class="card"><h2>By repository <span class="muted small">(click a row to chart it; click a heading to sort)</span></h2><div id="gh-table"></div></div>
      <div class="grid2">
        <div class="card"><h2>Referrers (last 14 days)</h2><div id="gh-refs"></div></div>
        <div class="card"><h2>Popular content (last 14 days)</h2><div id="gh-paths"></div></div>
      </div>
    </section>
    <section data-panel="settings" hidden>
      <div class="grid2">
        <form method="post" action="/insights" class="card">
          <h2>GitHub token</h2>
          <p class="muted">GitHub shares traffic only with a token. Create a <b>fine-grained</b> token at
            github.com → Settings → Developer settings → Fine-grained tokens: resource owner <b>codeROCK-IZZY</b>,
            <b>All repositories</b>, permissions <b>Administration: Read-only</b> (Metadata is added automatically).
            It is stored above the web root and never sent to the browser.</p>
          <p class="muted">Saved: <span id="token-hint">none</span></p>
          <input type="hidden" name="action" value="token"><input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <label>Token <input type="password" name="token" autocomplete="off" placeholder="github_pat_…"></label>
          <button type="submit">Save and collect</button>
        </form>
        <form method="post" action="/insights" class="card">
          <h2>Password</h2>
          <input type="hidden" name="action" value="password"><input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <label>Current <input type="password" name="current" autocomplete="current-password" required></label>
          <label>New (12+ characters) <input type="password" name="new" autocomplete="new-password" minlength="12" required></label>
          <button type="submit">Change password</button>
        </form>
      </div>
      <form method="post" action="/insights" class="card signout">
        <input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <p class="muted">Visits are counted without cookies: a visitor is a daily-salted hash, no IP is stored, and
          browsers with Do Not Track or Global Privacy Control are not counted. Weekly and monthly "visitors" add up
          daily unique visitors.</p>
        <button type="submit" class="ghost">Sign out</button>
      </form>
    </section>
  </main>
  <div class="tip" id="tip" hidden></div>
<?php endif; ?>
</body>
</html>
