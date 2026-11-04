<?php
/**
 * Shared setup for every page: strict errors, security headers, configuration and helpers.
 *
 * Configuration lives OUTSIDE the web root, in `contact-config.php` one directory above this site
 * (see README). It holds the contact recipient and the secret used to sign form tokens, so neither
 * ever appears in the repository or on a page.
 */

declare(strict_types=1);

const SITE_URL = 'https://israelrocklyn.info';
const ASSET_VERSION = '2';

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/lib/contact.php';

/** Settings with safe defaults; the config file overrides them. */
function config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $config = ['recipient' => '', 'secret' => '', 'from' => 'no-reply@israelrocklyn.info'];
    foreach ([dirname(__DIR__, 2) . '/contact-config.php', dirname(__DIR__) . '/contact-config.php'] as $file) {
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $config = array_merge($config, $loaded);
            }
            break;
        }
    }
    return $config;
}

/** Escape for HTML text and attribute values. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Versioned asset URL, so browsers can cache assets for a year and still get updates. */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/') . '?v=' . ASSET_VERSION;
}

/** Render a view with variables. Views escape their own output with e(). */
function view(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/views/' . $name . '.php';
}

/** The project catalogue, keyed by slug. */
function projects(): array
{
    static $projects = null;
    return $projects ??= require __DIR__ . '/data/projects.php';
}

function profile(): array
{
    static $profile = null;
    return $profile ??= require __DIR__ . '/data/profile.php';
}

/** Headers sent with every HTML page (Apache adds the same in .htaccess; PHP covers other servers). */
function send_page_headers(int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');
}
