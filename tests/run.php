<?php
/**
 * Test runner with no dependencies:  php tests/run.php
 * Unit tests for the contact form logic, and render tests for every page.
 */

declare(strict_types=1);

putenv('PORTFOLIO_OFFLINE=1'); // no GitHub calls from tests
require __DIR__ . '/../app/bootstrap.php';

$failures = 0;
$count = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $failures, $count;
    $count++;
    if ($ok) {
        echo "  ok    {$name}\n";
    } else {
        $failures++;
        echo "  FAIL  {$name}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
    }
}

/** Render a page script in a separate PHP process (pages call exit/header) and return its output. */
function render(string $script, array $get = [], array $server = []): string
{
    $root = dirname(__DIR__);
    $code = '$_GET = ' . var_export($get, true) . '; $_SERVER = array_merge($_SERVER, ' . var_export($server, true) . ');'
        . ' chdir(' . var_export($root, true) . '); require ' . var_export($root . '/' . $script, true) . ';';
    $cmd = 'PORTFOLIO_OFFLINE=1 ' . escapeshellarg(PHP_BINARY) . ' -d display_errors=stderr -r ' . escapeshellarg($code) . ' 2>&1';
    return (string) shell_exec($cmd);
}

function wellformed(string $html): array
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    $errors = array_filter(libxml_get_errors(), static function (LibXMLError $e): bool {
        // libxml predates HTML5 and reports unknown HTML5 elements; anything else is a real error.
        return !str_contains($e->message, 'invalid in Entity') && !preg_match('/Tag \w+ invalid/', $e->message);
    });
    libxml_clear_errors();
    return array_map(static fn (LibXMLError $e): string => trim($e->message) . ' line ' . $e->line, $errors);
}

echo "contact: tokens\n";
$secret = str_repeat('a', 64);
$t = contact_token($secret, 1_000_000_000);
check('a fresh token is too fast', contact_check_token($t, $secret, 1_000_000_001) === 'too_fast');
check('a token after a few seconds is valid', contact_check_token($t, $secret, 1_000_000_010) === null);
check('an old token has expired', contact_check_token($t, $secret, 1_000_000_000 + 7201) === 'expired');
check('a token signed with another secret is invalid', contact_check_token($t, str_repeat('b', 64), 1_000_000_010) === 'invalid');
check('a tampered timestamp is invalid', contact_check_token('1000000005' . substr($t, 10), $secret, 1_000_000_010) === 'invalid');
check('garbage is invalid', contact_check_token('hello', $secret) === 'invalid');

echo "contact: validation\n";
$good = ['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'subject' => 'Role', 'message' => 'We would like to talk about an AI engineer role.'];
[$data, $errors] = contact_validate($good);
check('a valid message passes', $errors === [], json_encode($errors));
[, $errors] = contact_validate(['name' => 'A', 'email' => 'nope', 'message' => 'short']);
check('name, email and message are checked', array_keys($errors) === ['name', 'email', 'message'], json_encode($errors));
[$data] = contact_validate(array_merge($good, ['name' => "Ada\r\nBcc: victim@example.com"]));
check('line breaks are removed from single-line fields', !str_contains($data['name'], "\n") && !str_contains($data['name'], "\r"));
[, $errors] = contact_validate(array_merge($good, ['email' => "ada@example.com\r\nBcc: x@example.com"]));
check('an email with a header injection is rejected', isset($errors['email']));
[, $errors] = contact_validate(array_merge($good, ['message' => str_repeat('see https://x.example ', 5)]));
check('more than 3 links is rejected', isset($errors['message']));
[$data] = contact_validate(array_merge($good, ['message' => "Hello\x00 there, this is long enough to pass."]));
check('control characters are stripped', !str_contains($data['message'], "\x00"));

echo "contact: mail\n";
[$data] = contact_validate($good);
$mail = contact_build_mail($data, 'owner@example.com', 'no-reply@israelrocklyn.info', '203.0.113.9', 0);
check('From is always this domain', str_contains($mail['headers'], 'From: Portfolio <no-reply@israelrocklyn.info>'));
check('Reply-To is the visitor', str_contains($mail['headers'], 'Reply-To: ada@example.com'));
check('the raw IP is not in the mail', !str_contains($mail['body'], '203.0.113.9'));
check('headers contain exactly six lines', count(explode("\r\n", $mail['headers'])) === 6);

echo "contact: rate limit\n";
$dir = sys_get_temp_dir() . '/rl-test-' . bin2hex(random_bytes(4));
$results = [];
for ($i = 0; $i < 6; $i++) {
    $results[] = contact_rate_limit_ok('198.51.100.1', $dir, 5000 + $i);
}
check('five messages an hour pass, the sixth is refused', $results === [true, true, true, true, true, false]);
check('another address is unaffected', contact_rate_limit_ok('198.51.100.2', $dir, 5010));
check('the window slides', contact_rate_limit_ok('198.51.100.1', $dir, 5000 + 3601));
check('no raw IP is written to disk', !str_contains(implode('', array_map('file_get_contents', glob($dir . '/*'))), '198.51.100.1')
    && !preg_grep('/198/', scandir($dir)));

echo "pages\n";
$home = render('index.php');
check('home has the animated portrait stage and the now line', str_contains($home, 'data-portrait') && str_contains($home, 'class="now mono"'));
check('home renders', str_contains($home, '<h1 id="hero-name"'), substr($home, 0, 300));
check('home embeds valid site data for the shell', is_array(json_decode((string) preg_replace('~^.*<script type="application/json" id="site-data">(.*?)</script>.*$~s', '$1', $home), true)));
check('home shows a trace hop per project', substr_count($home, 'class="trace-hop"') === count(projects()));
check('home is well-formed', wellformed($home) === [], implode('; ', array_slice(wellformed($home), 0, 3)));
foreach (projects() as $slug => $x) {
    check("home links to {$slug}", str_contains($home, '/work/' . $slug));
}
check('home shows the setup notice without a config', str_contains($home, 'contact form is being set up'));
check('home has no spoofable status text', !str_contains(render('index.php', ['contact' => '<b>hacked</b>']), 'hacked'));
foreach (projects() as $slug => $x) {
    $page = render('work.php', ['slug' => $slug]);
    check("case study {$slug} renders", str_contains($page, '<h1>' . e($x['title']) . '</h1>'));
    check("case study {$slug} is well-formed", wellformed($page) === [], implode('; ', array_slice(wellformed($page), 0, 3)));
}
foreach (notes() as $slug => $n) {
    $page = render('note.php', ['slug' => $slug]);
    check("note {$slug} renders", str_contains($page, '<h1>' . e($n['title']) . '</h1>'));
    check("note {$slug} is well-formed", wellformed($page) === [], implode('; ', array_slice(wellformed($page), 0, 3)));
}
$resume = render('resume.php');
check('the résumé lists every project', count(array_filter(array_keys(projects()), static fn ($s) => str_contains($resume, e(projects()[$s]['title'])))) === count(projects()));
check('the résumé carries a QR code to the site', str_contains($resume, 'qr-site.svg') && is_file(__DIR__ . '/../assets/img/qr-site.svg'));
check('every capability links to real projects', array_diff(array_merge(...array_values(profile()['capabilities'])), array_keys(projects())) === []);
check('every note belongs to a real project', array_diff(array_column(notes(), 'project'), array_keys(projects())) === []);
check('the trace covers every project once', count(array_unique(trace_order())) === count(projects()) && array_diff(trace_order(), array_keys(projects())) === []);
$shots = array_merge(...array_map('demo_shots', array_keys(projects())));
check('every demo screenshot has a caption', count($shots) > 20 && array_filter($shots, static fn ($x) => $x['caption'] === '') === []);
check('every public project has run-it-yourself commands', array_filter(projects(), static fn ($x) => $x['repo'] !== '' && count($x['run']) < 2) === []);
foreach (array_filter(projects(), static fn ($x) => $x['repo'] === '') as $slug => $x) {
    $page = render('work.php', ['slug' => $slug]);
    check("private product {$slug} links to its live site", str_starts_with((string) ($x['live_url'] ?? ''), 'https://') && str_contains($page, 'Open the live product'));
    check("private product {$slug} shows no code link or clone command", !str_contains($page, 'View the code') && !str_contains($page, 'git clone'));
}
check('the portrait exists', is_file(dirname(__DIR__) . '/assets/img/portrait.webp'));
check('an unknown project is a 404', str_contains(render('work.php', ['slug' => 'nope']), 'No route to'));
check('a malicious slug is a 404', str_contains(render('work.php', ['slug' => '../app/bootstrap']), 'No route to'));
check('an unknown note is a 404', str_contains(render('note.php', ['slug' => 'nope']), 'No route to'));
$sitemap = render('sitemap.php');
check('the sitemap lists every page', substr_count($sitemap, '<loc>') === count(projects()) + count(notes()) + 2);
check('the sitemap is valid XML', @simplexml_load_string($sitemap) !== false);

// ---------------------------------------------------------------- analytics and the private dashboard
$tmp = sys_get_temp_dir() . '/ir-analytics-test-' . getmypid();
putenv('ANALYTICS_DIR=' . $tmp);
$chrome = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/130.0 Safari/537.36';
$now = 1_800_000_000;
$client = ['ip' => '203.0.113.9', 'ua' => $chrome, 'lang' => 'en-GB,en;q=0.9', 'host' => 'israelrocklyn.info', 'now' => $now];
analytics_record(['type' => 'view', 'path' => '/', 'ref' => 'https://www.linkedin.com/feed/'], $client);
analytics_record(['type' => 'view', 'path' => '/work/x'], $client);
analytics_record(['type' => 'view', 'path' => '/'], ['ip' => '198.51.100.4'] + $client);
analytics_record(['type' => 'click', 'target' => 'github.com/codeROCK-IZZY/x'], $client);
analytics_record(['type' => 'leave', 'seconds' => 40], $client);
analytics_record(['type' => 'view', 'path' => '/'], ['ua' => 'Googlebot/2.1'] + $client);
$s = analytics_series(1, $now)['days'][0];
check('analytics counts views and daily unique visitors', $s['views'] === 3 && $s['visitors'] === 2, (string) json_encode($s));
check('analytics keeps bots out of the counts', $s['bots'] === 1);
check('analytics records clicks and engaged time', $s['clicks'] === 1 && $s['engaged_s'] === 40);
check('analytics groups referrers by site', ($s['referrers']['linkedin.com'] ?? 0) === 1 && ($s['referrers']['(direct)'] ?? 0) === 2);
$dayFile = (string) file_get_contents($tmp . '/day-' . gmdate('Y-m-d', $now) . '.json');
check('no IP address is ever stored', !str_contains($dayFile, '203.0.113') && !str_contains($dayFile, '198.51.100'));
analytics_compact(gmdate('Y-m-d', $now + 3 * 86400));
check('visitor hashes are dropped after two days', json_decode((string) file_get_contents($tmp . '/day-' . gmdate('Y-m-d', $now) . '.json'), true)['visitors'] === []);
check('the device and region come from the browser', analytics_device('iPhone Mobile') === 'mobile' && analytics_region('en-GB') === 'GB');
check('GitHub traffic merges into a history', ght_merge(['2026-01-01' => [5, 2]], ['views' => [['timestamp' => '2026-01-02T00:00:00Z', 'count' => 7, 'uniques' => 3]]], 'views') === ['2026-01-01' => [5, 2], '2026-01-02' => [7, 3]]);
$page = render('insights.php', [], ['REQUEST_METHOD' => 'GET']);
check('the dashboard asks for a password when signed out', str_contains($page, 'type="password"') && !str_contains($page, 'insights.js'));
check('the dashboard data needs a session', !str_contains(render('insights.php', ['data' => '1'], ['REQUEST_METHOD' => 'GET']), '"site"'));
check('the dashboard is not in the sitemap', !str_contains($sitemap, 'insights'));
array_map('unlink', glob($tmp . '/*.json') ?: []);

echo "\n{$count} checks, {$failures} failed\n";
exit($failures > 0 ? 1 : 0);
