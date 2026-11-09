<?php
/**
 * GitHub repository traffic (views, unique visitors, clones, unique cloners, referrers) kept beyond
 * GitHub's 14-day window.
 *
 * GitHub only returns the last 14 days, and only to a token with access to the repositories. Each
 * collection merges those 14 days into a history file above the web root, so weekly and monthly totals
 * build up over time. Collection runs when the dashboard is opened and, at most every few hours, after
 * an ordinary page view, so the history stays complete as long as the site gets a visit every two weeks.
 *
 * The token is a fine-grained, read-only token (Administration: read, Metadata: read) saved from the
 * dashboard's settings into a private file; it is never sent to the browser.
 */

declare(strict_types=1);

const GHT_STALE = 3 * 3600;

function ght_file(string $name): string
{
    return analytics_dir() . '/' . $name;
}

function ght_token(): string
{
    $s = analytics_settings();
    return is_string($s['github_token'] ?? null) ? $s['github_token'] : '';
}

function ght_history(): array
{
    $h = json_decode((string) @file_get_contents(ght_file('github-traffic.json')), true);
    return is_array($h) ? $h : ['updated' => 0, 'repos' => [], 'error' => ''];
}

/** @param array<string, string> $urls @return array<string, array{status: int, body: mixed}> */
function ght_get_many(array $urls, string $token): array
{
    $mh = curl_multi_init();
    $hs = [];
    foreach ($urls as $k => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json', 'User-Agent: israelrocklyn.info',
                'X-GitHub-Api-Version: 2022-11-28', 'Authorization: Bearer ' . $token],
        ]);
        curl_multi_add_handle($mh, $ch);
        $hs[$k] = $ch;
    }
    do {
        $st = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running && $st === CURLM_OK);
    $out = [];
    foreach ($hs as $k => $ch) {
        $out[$k] = ['status' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => json_decode((string) curl_multi_getcontent($ch), true)];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

/**
 * Merge one 14-day response into a repo's history (GitHub's own numbers win for every day it returns).
 * @param array<string, array{0: int, 1: int}> $hist
 * @return array<string, array{0: int, 1: int}>
 */
function ght_merge(array $hist, mixed $body, string $key): array
{
    foreach ((is_array($body) ? ($body[$key] ?? []) : []) as $row) {
        $day = substr((string) ($row['timestamp'] ?? ''), 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            $hist[$day] = [(int) ($row['count'] ?? 0), (int) ($row['uniques'] ?? 0)];
        }
    }
    ksort($hist);
    return $hist;
}

/** Collect if the history is stale (or $force). Returns the history either way. */
function ght_collect(bool $force = false): array
{
    $h = ght_history();
    $token = ght_token();
    if ($token === '' || !function_exists('curl_multi_init') || getenv('PORTFOLIO_OFFLINE')) {
        return $h;
    }
    if (!$force && ($h['updated'] ?? 0) > time() - GHT_STALE) {
        return $h;
    }
    $lock = @fopen(ght_file('github-traffic.lock'), 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        return $h; // another request is collecting
    }
    try {
        $api = 'https://api.github.com/';
        $list = ght_get_many(['list' => $api . 'user/repos?affiliation=owner&per_page=100&sort=pushed'], $token)['list'];
        if ($list['status'] !== 200 || !is_array($list['body'])) {
            $h['error'] = 'GitHub answered ' . $list['status'] . ' for the repository list; check the token.';
            $h['updated'] = time() - GHT_STALE + 600; // retry in 10 minutes
            @file_put_contents(ght_file('github-traffic.json'), json_encode($h), LOCK_EX);
            return $h;
        }
        $urls = [];
        $meta = [];
        foreach ($list['body'] as $r) {
            $name = (string) ($r['name'] ?? '');
            $full = (string) ($r['full_name'] ?? '');
            if ($name === '' || !empty($r['fork'])) {
                continue;
            }
            $meta[$name] = ['stars' => (int) ($r['stargazers_count'] ?? 0), 'forks' => (int) ($r['forks_count'] ?? 0),
                'watchers' => (int) ($r['watchers_count'] ?? 0), 'private' => !empty($r['private']),
                'pushed' => (string) ($r['pushed_at'] ?? '')];
            $urls[$name . '|views'] = $api . 'repos/' . $full . '/traffic/views';
            $urls[$name . '|clones'] = $api . 'repos/' . $full . '/traffic/clones';
            $urls[$name . '|referrers'] = $api . 'repos/' . $full . '/traffic/popular/referrers';
            $urls[$name . '|paths'] = $api . 'repos/' . $full . '/traffic/popular/paths';
        }
        $res = ght_get_many($urls, $token);
        $denied = 0;
        foreach ($meta as $name => $m) {
            $repo = $h['repos'][$name] ?? ['views' => [], 'clones' => []];
            $repo = array_merge($repo, $m);
            $v = $res[$name . '|views'] ?? ['status' => 0, 'body' => null];
            $c = $res[$name . '|clones'] ?? ['status' => 0, 'body' => null];
            if ($v['status'] === 200) {
                $repo['views'] = ght_merge($repo['views'] ?? [], $v['body'], 'views');
            } else {
                $denied++;
            }
            if ($c['status'] === 200) {
                $repo['clones'] = ght_merge($repo['clones'] ?? [], $c['body'], 'clones');
            }
            $ref = $res[$name . '|referrers'] ?? null;
            if (($ref['status'] ?? 0) === 200 && is_array($ref['body'])) {
                $repo['referrers'] = array_map(static fn (array $x): array => ['source' => (string) ($x['referrer'] ?? ''),
                    'count' => (int) ($x['count'] ?? 0), 'uniques' => (int) ($x['uniques'] ?? 0)], $ref['body']);
            }
            $paths = $res[$name . '|paths'] ?? null;
            if (($paths['status'] ?? 0) === 200 && is_array($paths['body'])) {
                $repo['paths'] = array_map(static fn (array $x): array => ['path' => (string) ($x['path'] ?? ''),
                    'count' => (int) ($x['count'] ?? 0), 'uniques' => (int) ($x['uniques'] ?? 0)], array_slice($paths['body'], 0, 10));
            }
            $h['repos'][$name] = $repo;
        }
        $h['updated'] = time();
        $h['error'] = $denied ? "$denied repositories refused traffic data; the token needs Administration: read on them." : '';
        @file_put_contents(ght_file('github-traffic.json'), json_encode($h, JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $h;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Private settings (password hash, GitHub token), outside the web root, readable only by this account. */
function analytics_settings(): array
{
    $s = json_decode((string) @file_get_contents(ght_file('settings.json')), true);
    $s = is_array($s) ? $s : [];
    $hash = config()['insights_password_hash'] ?? '';
    if (empty($s['password_hash']) && is_string($hash) && $hash !== '') {
        $s['password_hash'] = $hash;
    }
    return $s;
}

function analytics_save_settings(array $changes): void
{
    $s = json_decode((string) @file_get_contents(ght_file('settings.json')), true);
    $s = array_merge(is_array($s) ? $s : [], $changes);
    $file = ght_file('settings.json');
    @file_put_contents($file, json_encode($s), LOCK_EX);
    @chmod($file, 0600);
}
