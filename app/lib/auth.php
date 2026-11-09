<?php
/**
 * Sign-in for the private insights dashboard: one owner, one password (bcrypt), a short session.
 *
 * The session cookie is Secure, HttpOnly and SameSite=Strict, its id is regenerated at sign-in, and it
 * expires after 8 idle hours. Failed attempts are throttled per network: after 5 failures in 15 minutes,
 * further attempts are refused until the window passes. Every form carries a CSRF token.
 */

declare(strict_types=1);

const AUTH_IDLE = 8 * 3600;
const AUTH_MAX_FAILS = 5;
const AUTH_WINDOW = 900;

function auth_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $dir = analytics_dir() . '/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
    }
    session_name('ir_insights');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/insights', 'secure' => !getenv('INSIGHTS_DEV_HTTP'), 'httponly' => true, 'samesite' => 'Strict']);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) AUTH_IDLE);
    session_start();
}

function auth_signed_in(): bool
{
    auth_start();
    $ok = !empty($_SESSION['owner']) && ($_SESSION['seen'] ?? 0) > time() - AUTH_IDLE;
    if ($ok) {
        $_SESSION['seen'] = time();
    }
    return $ok;
}

function auth_csrf(): string
{
    auth_start();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function auth_csrf_ok(mixed $token): bool
{
    auth_start();
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function auth_client_key(): string
{
    return substr(hash('sha256', 'auth|' . ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 16);
}

/** @return array<string, list<int>> */
function auth_failures(): array
{
    $f = json_decode((string) @file_get_contents(analytics_dir() . '/auth-failures.json'), true);
    return is_array($f) ? $f : [];
}

function auth_locked(?int $now = null): bool
{
    $now ??= time();
    $recent = array_filter(auth_failures()[auth_client_key()] ?? [], static fn (int $t): bool => $t > $now - AUTH_WINDOW);
    return count($recent) >= AUTH_MAX_FAILS;
}

function auth_note_failure(): void
{
    $f = auth_failures();
    $now = time();
    foreach ($f as $k => $times) {
        $f[$k] = array_values(array_filter($times, static fn (int $t): bool => $t > $now - AUTH_WINDOW));
        if ($f[$k] === []) {
            unset($f[$k]);
        }
    }
    $f[auth_client_key()][] = $now;
    @file_put_contents(analytics_dir() . '/auth-failures.json', json_encode($f), LOCK_EX);
}

function auth_attempt(string $password): bool
{
    if (auth_locked()) {
        return false;
    }
    $hash = (string) (analytics_settings()['password_hash'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        auth_note_failure();
        usleep(400000);
        return false;
    }
    auth_start();
    session_regenerate_id(true);
    $_SESSION['owner'] = true;
    $_SESSION['seen'] = time();
    return true;
}

function auth_sign_out(): void
{
    auth_start();
    $_SESSION = [];
    session_destroy();
}

/** Headers for every insights response: never cached, never indexed, never framed. */
function auth_private_headers(string $type = 'text/html; charset=utf-8'): void
{
    header('Content-Type: ' . $type);
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
}
