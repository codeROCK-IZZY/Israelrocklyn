<?php
/**
 * Live repository status from the GitHub API: the latest CI run on main and the last push, per repo.
 *
 * Fetched server-side (so the page keeps a strict same-origin CSP), in parallel with curl_multi, and
 * cached for 30 minutes in a file above the web root. If GitHub is slow or unreachable, the last good
 * cache is used, and with no cache at all the page simply shows no live badges. It never fails a page.
 */

declare(strict_types=1);

const GITHUB_OWNER = 'codeROCK-IZZY';
const GITHUB_TTL = 1800;

/** @return array{checked: int, repos: array<string, array{ci: string, pushed: string}>}|null */
function github_status(array $repos): ?array
{
    $file = github_cache_file();
    $cached = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    if (is_array($cached) && ($cached['checked'] ?? 0) > time() - GITHUB_TTL) {
        return $cached;
    }
    if (!function_exists('curl_multi_init') || getenv('PORTFOLIO_OFFLINE')) {
        return is_array($cached) ? $cached : null;
    }
    $fresh = github_fetch($repos);
    if ($fresh === null) {
        // Back off: keep serving the old data, try again later.
        if (is_array($cached)) {
            $cached['checked'] = time() - GITHUB_TTL + 300;
            @file_put_contents($file, json_encode($cached), LOCK_EX);
        }
        return is_array($cached) ? $cached : null;
    }
    @file_put_contents($file, json_encode($fresh), LOCK_EX);
    return $fresh;
}

function github_cache_file(): string
{
    $dir = dirname(__DIR__, 3) . '/.portfolio-cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return (is_dir($dir) && is_writable($dir) ? $dir : sys_get_temp_dir()) . '/github-status.json';
}

/** @return array{checked: int, repos: array<string, array{ci: string, pushed: string}>}|null */
function github_fetch(array $repos): ?array
{
    $mh = curl_multi_init();
    $handles = [];
    foreach ($repos as $repo) {
        foreach (['runs' => "/actions/runs?branch=main&per_page=1&exclude_pull_requests=true", 'repo' => ''] as $kind => $path) {
            $ch = curl_init('https://api.github.com/repos/' . GITHUB_OWNER . '/' . rawurlencode($repo) . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json', 'User-Agent: israelrocklyn.info', 'X-GitHub-Api-Version: 2022-11-28'],
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$repo][$kind] = $ch;
        }
    }
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 0.5);
        }
    } while ($running && $status === CURLM_OK);

    $out = ['checked' => time(), 'repos' => []];
    $ok = 0;
    foreach ($handles as $repo => $pair) {
        $runs = json_decode((string) curl_multi_getcontent($pair['runs']), true);
        $meta = json_decode((string) curl_multi_getcontent($pair['repo']), true);
        $run = $runs['workflow_runs'][0] ?? null;
        $ci = 'unknown';
        if (is_array($run)) {
            $ci = $run['status'] !== 'completed' ? 'running' : ($run['conclusion'] === 'success' ? 'passing' : 'failing');
            $ok++;
        }
        $out['repos'][$repo] = ['ci' => $ci, 'pushed' => is_array($meta) ? (string) ($meta['pushed_at'] ?? '') : ''];
        foreach ($pair as $ch) {
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
    }
    curl_multi_close($mh);
    return $ok > 0 ? $out : null; // nothing usable (rate limit, network): keep the old cache
}

/** "3 hours ago" style. */
function ago(string $iso): string
{
    $t = strtotime($iso);
    if ($t === false) {
        return '';
    }
    $d = max(0, time() - $t);
    foreach ([[86400 * 30, 'month'], [86400 * 7, 'week'], [86400, 'day'], [3600, 'hour'], [60, 'minute']] as [$s, $unit]) {
        if ($d >= $s) {
            $n = intdiv($d, $s);
            return $n . ' ' . $unit . ($n > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}
