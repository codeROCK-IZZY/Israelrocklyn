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
const ASSET_VERSION = '39';

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/lib/contact.php';
require __DIR__ . '/lib/github.php';
require __DIR__ . '/lib/analytics.php';
require __DIR__ . '/lib/ghtraffic.php';
require __DIR__ . '/lib/auth.php';

/** The visitor's address: the CDN passes it in X-Forwarded-For; REMOTE_ADDR is the edge otherwise. */
function client_ip(): string
{
    $xff = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    $first = trim(explode(',', $xff)[0]);
    return filter_var($first, FILTER_VALIDATE_IP) ? $first : (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

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

function notes(): array
{
    static $notes = null;
    return $notes ??= require __DIR__ . '/data/notes.php';
}

/**
 * Demo screenshots for a project: assets/img/demo/<slug>/NN-name.webp, captioned by NN-name.txt.
 * @return list<array{src: string, caption: string}>
 */
function demo_shots(string $slug): array
{
    $dir = dirname(__DIR__) . '/assets/img/demo/' . $slug;
    $out = [];
    foreach (glob($dir . '/*.webp') ?: [] as $file) {
        $caption = @file_get_contents(substr($file, 0, -5) . '.txt');
        $out[] = ['src' => '/assets/img/demo/' . $slug . '/' . basename($file), 'caption' => trim((string) $caption)];
    }
    return $out;
}

/** Saved colour theme (set by the theme toggle), so the right theme renders before any script runs. */
function theme(): string
{
    return ($_COOKIE['theme'] ?? '') === 'light' ? 'light' : 'dark';
}

/** Live CI status for every project repository (cached; may be null). */
function live_status(): ?array
{
    static $status = false;
    if ($status === false) {
        $status = github_status(array_keys(projects()));
    }
    return $status;
}

/** Projects in the order a request travels through them (the trace on the home page). */
function trace_order(): array
{
    return ['distributed-ai-inference', 'ai-research-engine', 'intelligent-document-ai', 'multimodal-ai-assistant',
        'autonomous-business-platform', 'fraud-detection-engine', 'ai-predictive-maintenance', 'aws-mlops-platform',
        'ai-developer-intelligence', 'aws-serverless-event-platform', 'aws-security-guardrails', 'aws-finops-optimizer', 'aws-data-lakehouse', 'aws-resilience-lab', 'llm-guardrails-gateway', 'llm-evaluation-platform', 'recommender-system', 'demand-forecasting', 'entity-resolution', 'experimentation-platform', 'feature-store', 'visual-anomaly-detection', 'wordpress-lead-capture', 'woocommerce-pricing-rules', 'wordpress-ai-assistant', 'wordpress-business-blocks', 'wordpress-block-theme', 'ai-voice-receptionist', 'enterprise-multi-agent-workforce', 'video-intelligence-platform', 'ai-contract-lifecycle', 'autonomous-software-delivery', 'meeting-operations-ai', 'internal-developer-platform', 'ai-incident-response', 'kubernetes-management-platform', 'cloud-service-catalog', 'enterprise-aws-cloud-platform'];
}

/** Data for the client-side shell and command palette (embedded as JSON; no extra request). */
function site_data(): array
{
    $p = profile();
    return [
        'name' => $p['name'],
        'headline' => $p['headline'],
        'location' => $p['location'],
        'github' => $p['github'],
        'linkedin' => $p['linkedin'],
        'roles' => $p['roles'],
        'education' => array_map(static fn (array $e): string => $e['degree'] . ' (' . $e['focus'] . ')', $p['education']),
        'skills' => $p['skills'],
        'projects' => array_map(static fn (array $x, string $slug): array => [
            'slug' => $slug, 'title' => $x['title'], 'tagline' => $x['tagline'], 'repo' => $x['repo'],
            'results' => $x['results'], 'stack' => $x['stack'], 'hop' => $x['hop'],
        ], projects(), array_keys(projects())),
        'notes' => array_map(static fn (array $n, string $slug): array => ['slug' => $slug, 'title' => $n['title']], notes(), array_keys(notes())),
    ];
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
