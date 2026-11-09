<?php
/**
 * Analytics beacon: POST a small JSON event ({type: view|click|leave, ...}); answers 204 with no body.
 * No cookies are read or set. See app/lib/analytics.php for what is stored.
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}
$raw = (string) file_get_contents('php://input', false, null, 0, 2048);
$event = json_decode($raw, true);
$ip = client_ip();
if (is_array($event) && analytics_allow($ip)) {
    analytics_record($event, [
        'ip' => $ip,
        'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300),
        'lang' => (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''),
        'host' => (string) parse_url(SITE_URL, PHP_URL_HOST),
    ]);
}
http_response_code(204);
// Keep GitHub traffic history current without making the visitor wait for it.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
    ght_collect();
}
