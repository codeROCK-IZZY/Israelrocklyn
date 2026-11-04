<?php
/**
 * Development only: mirrors the .htaccess rules for PHP's built-in server.
 *   php -S localhost:8000 router.php
 * Blocked on the live site by .htaccess.
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('~^/(app|tests|\.github|\.git)(/|$)|^/(README\.md|router\.php|contact-config)~', $path)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return true;
}
if (preg_match('~^/work/([a-z0-9-]+)/?$~', $path, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/work.php';
    return true;
}
if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false; // static file or a real PHP script
}
if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/index.php';
    return true;
}
require __DIR__ . '/404.php';
return true;
