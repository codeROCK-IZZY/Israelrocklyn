<?php
/**
 * Contact form endpoint. Works with and without JavaScript:
 *  - fetch() with "Accept: application/json" gets a JSON answer, shown inline by assets/js/site.js;
 *  - a plain form post is redirected back to the contact section with a status (Post/Redirect/Get).
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function respond(string $code, array $errors = [], int $status = 200): never
{
    global $wantsJson;
    $ok = $code === 'sent';
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'code' => $code, 'message' => CONTACT_MESSAGES[$code], 'errors' => (object) $errors],
            JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: /?contact=' . $code . '#contact', true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /#contact', true, 303);
    exit;
}

$config = config();
if ($config['recipient'] === '' || $config['secret'] === '') {
    error_log('contact form: contact-config.php is missing or incomplete');
    respond('unavailable', [], 503);
}

// Honeypot: pretend success so bots learn nothing.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    respond('sent');
}

$tokenProblem = contact_check_token((string) ($_POST['token'] ?? ''), $config['secret']);
if ($tokenProblem === 'too_fast') {
    respond('too_fast', [], 429);
}
if ($tokenProblem !== null) {
    respond('expired', [], 400);
}

[$data, $errors] = contact_validate($_POST);
if ($errors) {
    respond('invalid', $errors, 422);
}

$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
// Above the web root (the hosting home directory), never served.
$rateDir = rtrim($config['rate_dir'] ?? (dirname(__DIR__) . '/.contact-rate'), '/');
if (!contact_rate_limit_ok($ip, $rateDir)) {
    respond('rate', [], 429);
}

$mail = contact_build_mail($data, $config['recipient'], $config['from'], $ip);
$sent = mail($mail['to'], $mail['subject'], $mail['body'], $mail['headers'], '-f' . $config['from']);
if (!$sent) {
    error_log('contact form: mail() failed');
    respond('failed', [], 500);
}
respond('sent');
