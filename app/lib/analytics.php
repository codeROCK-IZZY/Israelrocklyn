<?php
/**
 * Cookie-free visit analytics for this site, stored as one small JSON file per day above the web root.
 *
 * A visitor is counted once per day as a hash of (daily random salt, IP, user agent). The salt changes
 * every day and is never stored with the counts, so a visitor cannot be followed from one day to the
 * next and no IP address is ever written to disk. Browsers that send Do Not Track or Global Privacy
 * Control are not tracked at all (the beacon script checks before sending).
 */

declare(strict_types=1);

const ANALYTICS_KEEP_HASHES_DAYS = 2;   // per-visitor hashes are kept for today and yesterday only

function analytics_dir(): string
{
    $dir = dirname(__DIR__, 3) . '/.portfolio-cache/analytics';
    if (getenv('ANALYTICS_DIR')) {
        $dir = (string) getenv('ANALYTICS_DIR');
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

function analytics_today(?int $now = null): string
{
    return gmdate('Y-m-d', $now ?? time());
}

/** Today's salt; created on first use, replaced the next day. */
function analytics_salt(string $day): string
{
    $file = analytics_dir() . '/salt.json';
    $data = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    if (is_array($data) && ($data['day'] ?? '') === $day && is_string($data['salt'] ?? null)) {
        return $data['salt'];
    }
    $salt = bin2hex(random_bytes(16));
    @file_put_contents($file, json_encode(['day' => $day, 'salt' => $salt]), LOCK_EX);
    return $salt;
}

function analytics_is_bot(string $ua): bool
{
    return $ua === '' || (bool) preg_match('/bot|crawl|spider|slurp|preview|headless|lighthouse|monitor|curl|wget|python|httpclient|facebookexternalhit|embedly|whatsapp/i', $ua);
}

function analytics_device(string $ua): string
{
    if (preg_match('/iPad|Tablet/i', $ua)) {
        return 'tablet';
    }
    return preg_match('/Mobi|Android|iPhone/i', $ua) ? 'mobile' : 'desktop';
}

function analytics_browser(string $ua): string
{
    foreach (['Edg/' => 'Edge', 'OPR/' => 'Opera', 'SamsungBrowser' => 'Samsung', 'Firefox/' => 'Firefox',
        'Chrome/' => 'Chrome', 'Safari/' => 'Safari'] as $needle => $name) {
        if (str_contains($ua, $needle)) {
            return $name;
        }
    }
    return 'Other';
}

/** The referring site as a bare host, '(direct)' when none, '(internal)' for this site. */
function analytics_referrer(string $ref, string $ownHost): string
{
    $host = strtolower((string) parse_url($ref, PHP_URL_HOST));
    $host = preg_replace('/^(www\.|m\.|l\.|lm\.)/', '', $host) ?? $host;
    if ($host === '') {
        return '(direct)';
    }
    return $host === $ownHost ? '(internal)' : substr($host, 0, 80);
}

/** Region from Accept-Language ("en-GB" -> GB); a rough hint of where visitors are, without geolocation. */
function analytics_region(string $acceptLanguage): string
{
    return preg_match('/^[a-z]{2,3}-([A-Z]{2})\b/', $acceptLanguage, $m) ? $m[1] : '??';
}

/**
 * Update one day's counters under an exclusive lock.
 * @param callable(array): array $change
 */
function analytics_update(string $day, callable $change): void
{
    $file = analytics_dir() . '/day-' . $day . '.json';
    $fh = @fopen($file, 'c+');
    if ($fh === false) {
        return;
    }
    try {
        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $data = $raw ? json_decode($raw, true) : null;
        $data = is_array($data) ? $data : analytics_empty_day();
        $data = $change($data);
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, (string) json_encode($data, JSON_UNESCAPED_SLASHES));
        fflush($fh);
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

function analytics_empty_day(): array
{
    return ['views' => 0, 'visitors' => [], 'uv' => 0, 'pages' => [], 'referrers' => [], 'devices' => [],
        'browsers' => [], 'regions' => [], 'clicks' => [], 'engaged_s' => 0, 'engaged_n' => 0, 'bots' => 0];
}

function analytics_inc(array &$map, string $key, int $by = 1): void
{
    $map[$key] = ($map[$key] ?? 0) + $by;
}

/**
 * Record one beacon event.
 * @param array{type?: mixed, path?: mixed, ref?: mixed, target?: mixed, seconds?: mixed} $event
 * @param array{ip: string, ua: string, lang: string, host: string, now?: int} $client
 */
function analytics_record(array $event, array $client): bool
{
    $type = (string) ($event['type'] ?? '');
    $now = $client['now'] ?? time();
    $day = analytics_today($now);
    if (analytics_is_bot($client['ua'])) {
        analytics_update($day, static function (array $d): array {
            $d['bots']++;
            return $d;
        });
        return false;
    }
    $path = substr(preg_replace('#[^a-z0-9/_\-.]#i', '', (string) ($event['path'] ?? '/')) ?: '/', 0, 120);
    $visitor = substr(hash('sha256', analytics_salt($day) . '|' . $client['ip'] . '|' . $client['ua']), 0, 16);
    if ($type === 'view') {
        analytics_update($day, static function (array $d) use ($path, $visitor, $event, $client): array {
            $d['views']++;
            if (!isset($d['visitors'][$visitor])) {
                $d['visitors'][$visitor] = 1;
                $d['uv']++;
                analytics_inc($d['devices'], analytics_device($client['ua']));
                analytics_inc($d['browsers'], analytics_browser($client['ua']));
                analytics_inc($d['regions'], analytics_region($client['lang']));
            }
            analytics_inc($d['pages'], $path);
            analytics_inc($d['referrers'], analytics_referrer((string) ($event['ref'] ?? ''), $client['host']));
            return $d;
        });
        analytics_compact($day);
        return true;
    }
    if ($type === 'click') {
        $target = substr(preg_replace('/[^\w:\/.\-@ ]/u', '', (string) ($event['target'] ?? '')) ?? '', 0, 120);
        if ($target === '') {
            return false;
        }
        analytics_update($day, static function (array $d) use ($target): array {
            analytics_inc($d['clicks'], $target);
            return $d;
        });
        return true;
    }
    if ($type === 'leave') {
        $s = (int) ($event['seconds'] ?? 0);
        if ($s < 1 || $s > 3600) {
            return false;
        }
        analytics_update($day, static function (array $d) use ($s): array {
            $d['engaged_s'] += $s;
            $d['engaged_n']++;
            return $d;
        });
        return true;
    }
    return false;
}

/** Drop per-visitor hashes from days older than the window; only the counts remain. */
function analytics_compact(string $today): void
{
    $limit = gmdate('Y-m-d', (int) strtotime($today . ' -' . ANALYTICS_KEEP_HASHES_DAYS . ' days'));
    foreach (glob(analytics_dir() . '/day-*.json') ?: [] as $file) {
        $day = substr(basename($file, '.json'), 4);
        if ($day > $limit) {
            continue;
        }
        $raw = json_decode((string) @file_get_contents($file), true);
        if (is_array($raw) && !empty($raw['visitors'])) {
            analytics_update($day, static function (array $d): array {
                $d['visitors'] = [];
                return $d;
            });
        }
    }
}

/**
 * Daily series and totals for the last $days days (oldest first).
 * @return array{days: list<array<string, mixed>>, totals: array<string, mixed>}
 */
function analytics_series(int $days, ?int $now = null): array
{
    $now ??= time();
    $out = [];
    $tot = ['pages' => [], 'referrers' => [], 'devices' => [], 'browsers' => [], 'regions' => [], 'clicks' => []];
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = gmdate('Y-m-d', $now - $i * 86400);
        $file = analytics_dir() . '/day-' . $day . '.json';
        $d = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        $d = is_array($d) ? array_merge(analytics_empty_day(), $d) : analytics_empty_day();
        $out[] = ['date' => $day, 'views' => $d['views'], 'visitors' => $d['uv'], 'clicks' => array_sum($d['clicks']),
            'engaged_s' => $d['engaged_s'], 'engaged_n' => $d['engaged_n'], 'bots' => $d['bots'],
            'pages' => $d['pages'], 'referrers' => $d['referrers'], 'click_targets' => $d['clicks']];
        foreach (['pages', 'referrers', 'devices', 'browsers', 'regions', 'clicks'] as $k) {
            foreach ($d[$k] as $key => $n) {
                analytics_inc($tot[$k], (string) $key, (int) $n);
            }
        }
    }
    foreach ($tot as &$map) {
        arsort($map);
    }
    return ['days' => $out, 'totals' => $tot];
}

/** A small per-IP-hash rate limit for the beacon, so a script can't flood the counters. */
function analytics_allow(string $ip, int $perMinute = 60, ?int $now = null): bool
{
    $now ??= time();
    $file = analytics_dir() . '/rate-' . gmdate('YmdHi', $now) . '.json';
    $key = substr(hash('sha256', 'rate|' . $ip), 0, 12);
    $ok = true;
    $fh = @fopen($file, 'c+');
    if ($fh === false) {
        return true;
    }
    flock($fh, LOCK_EX);
    $map = json_decode((string) stream_get_contents($fh), true);
    $map = is_array($map) ? $map : [];
    $map[$key] = ($map[$key] ?? 0) + 1;
    $ok = $map[$key] <= $perMinute;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, (string) json_encode($map));
    flock($fh, LOCK_UN);
    fclose($fh);
    foreach (glob(analytics_dir() . '/rate-*.json') ?: [] as $old) {
        if ($old !== $file && @filemtime($old) < $now - 120) {
            @unlink($old);
        }
    }
    return $ok;
}
