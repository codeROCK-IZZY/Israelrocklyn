<?php
/**
 * Contact form: validation, anti-spam and sending. Pure functions where possible, so they are unit-tested
 * (tests/run.php).
 *
 * Spam and abuse defences, cheapest first:
 *  1. Honeypot: a field hidden from people; bots fill it in.
 *  2. Signed form token: issued when the page renders, as "<issued-at>.<HMAC>". It proves the form came
 *     from this site (no cross-site posting), and it must be at least 3 seconds and at most 2 hours old,
 *     which stops instant bot submissions and replays of old tokens.
 *  3. Rate limit: at most 5 messages per hour per IP address (hashed before storage).
 *  4. Strict validation, and no user input in mail headers except a validated Reply-To.
 */

declare(strict_types=1);

const CONTACT_MIN_SECONDS = 3;
const CONTACT_MAX_AGE = 7200;
const CONTACT_RATE_LIMIT = 5;
const CONTACT_RATE_WINDOW = 3600;

/** The only messages the form can show. URLs carry the code, never text, so links can't spoof messages. */
const CONTACT_MESSAGES = [
    'sent' => 'Thank you. Your message has been sent, and I will reply by email.',
    'invalid' => 'Please check the highlighted fields.',
    'too_fast' => 'That was quick. Please wait a few seconds and send again.',
    'expired' => 'This form has expired. Please reload the page and try again.',
    'rate' => 'Too many messages from your connection. Please try again in an hour.',
    'unavailable' => 'The contact form is not available right now. Please try again later.',
    'failed' => 'Your message could not be sent. Please try again later.',
];

function contact_token(string $secret, ?int $now = null): string
{
    $issued = (string) ($now ?? time());
    return $issued . '.' . hash_hmac('sha256', 'contact|' . $issued, $secret);
}

/** Returns null when the token is valid, otherwise a reason code. */
function contact_check_token(string $token, string $secret, ?int $now = null): ?string
{
    $now ??= time();
    if (!preg_match('/^(\d{9,11})\.([0-9a-f]{64})$/', $token, $m)) {
        return 'invalid';
    }
    $expected = hash_hmac('sha256', 'contact|' . $m[1], $secret);
    if (!hash_equals($expected, $m[2])) {
        return 'invalid';
    }
    $age = $now - (int) $m[1];
    if ($age < CONTACT_MIN_SECONDS) {
        return 'too_fast';
    }
    if ($age > CONTACT_MAX_AGE) {
        return 'expired';
    }
    return null;
}

/**
 * Validates and normalises the submitted fields.
 *
 * @return array{0: array{name: string, email: string, subject: string, message: string}, 1: array<string, string>}
 */
function contact_validate(array $in): array
{
    $clean = static fn ($v): string => trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $v) ?? '');
    $data = [
        'name' => $clean($in['name'] ?? ''),
        'email' => $clean($in['email'] ?? ''),
        'subject' => $clean($in['subject'] ?? ''),
        'message' => $clean($in['message'] ?? ''),
    ];
    // Names and subjects are single lines: line breaks there are header-injection attempts.
    foreach (['name', 'subject', 'email'] as $field) {
        $data[$field] = str_replace(["\r", "\n"], ' ', $data[$field]);
    }
    $errors = [];
    $len = static fn (string $s): int => mb_strlen($s, 'UTF-8');
    if ($len($data['name']) < 2 || $len($data['name']) > 100) {
        $errors['name'] = 'Please enter your name (2–100 characters).';
    }
    if ($len($data['email']) > 254 || filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($len($data['subject']) > 150) {
        $errors['subject'] = 'Please keep the subject under 150 characters.';
    }
    if ($len($data['message']) < 20 || $len($data['message']) > 5000) {
        $errors['message'] = 'Please write a message of 20–5,000 characters.';
    }
    if (preg_match_all('~https?://~i', $data['message']) > 3) {
        $errors['message'] = 'Please include at most 3 links.';
    }
    return [$data, $errors];
}

/** Sliding-window rate limit per client. The IP is hashed, so no raw addresses are stored. */
function contact_rate_limit_ok(string $ip, string $dir, ?int $now = null): bool
{
    $now ??= time();
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return true; // fail open: storage problems must not take the form down
    }
    $file = $dir . '/' . hash('sha256', 'rate|' . $ip) . '.json';
    $fh = @fopen($file, 'c+');
    if ($fh === false) {
        return true;
    }
    try {
        flock($fh, LOCK_EX);
        $hits = json_decode(stream_get_contents($fh) ?: '[]', true);
        $hits = array_values(array_filter(is_array($hits) ? $hits : [], static fn ($t) => is_int($t) && $t > $now - CONTACT_RATE_WINDOW));
        if (count($hits) >= CONTACT_RATE_LIMIT) {
            return false;
        }
        $hits[] = $now;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($hits));
        return true;
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

/**
 * Builds the e-mail. Only the validated Reply-To comes from the visitor; From is always this domain, so
 * SPF/DKIM pass and the message can't be spoofed as coming from the visitor.
 *
 * @return array{to: string, subject: string, body: string, headers: string}
 */
function contact_build_mail(array $data, string $to, string $from, string $ip, ?int $now = null): array
{
    $subject = 'Portfolio message from ' . $data['name'] . ($data['subject'] !== '' ? ': ' . $data['subject'] : '');
    $body = "New message from the contact form on israelrocklyn.info\n\n"
        . 'Name:    ' . $data['name'] . "\n"
        . 'Email:   ' . $data['email'] . "\n"
        . 'Subject: ' . ($data['subject'] !== '' ? $data['subject'] : '(none)') . "\n"
        . 'Sent:    ' . gmdate('Y-m-d H:i', $now ?? time()) . " UTC\n"
        . 'IP hash: ' . substr(hash('sha256', $ip), 0, 12) . "\n\n"
        . wordwrap($data['message'], 76, "\n", false) . "\n";
    $headers = implode("\r\n", [
        'From: Portfolio <' . $from . '>',
        'Reply-To: ' . $data['email'],
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: israelrocklyn.info',
    ]);
    return [
        'to' => $to,
        'subject' => mb_encode_mimeheader(mb_substr($subject, 0, 200, 'UTF-8'), 'UTF-8', 'Q'),
        'body' => $body,
        'headers' => $headers,
    ];
}
