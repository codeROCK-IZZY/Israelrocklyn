<?php
/** Live CI badge for one repository. @var string $slug */
$live = live_status();
$r = $live['repos'][$slug] ?? null;
if ($r === null || $r['ci'] === 'unknown') {
    return;
}
$text = ['passing' => 'CI passing', 'running' => 'CI running', 'failing' => 'CI failing'][$r['ci']] ?? '';
?>
<span class="ci ci-<?= e($r['ci']) ?>" title="Latest GitHub Actions run on main<?= $r['pushed'] !== '' ? ' · last push ' . e(ago($r['pushed'])) : '' ?>"><?= e($text) ?></span>
