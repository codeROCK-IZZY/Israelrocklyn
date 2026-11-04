<?php
/**
 * <head>, header and section rail.
 * @var string $title @var string $description @var string $path
 * @var array|null $jsonld @var bool|null $rail  show the numbered section rail (home page)
 */
$p = profile();
$canonical = SITE_URL . $path;
$live = live_status();
$green = $live ? count(array_filter($live['repos'], static fn (array $r): bool => $r['ci'] === 'passing')) : null;
$sections = ['top' => 'Intro', 'trace' => 'Trace', 'work' => 'Work', 'capabilities' => 'Capabilities', 'notes' => 'Notes',
    'shell' => 'Shell', 'services' => 'Web & growth', 'contact' => 'Contact'];
?>
<!doctype html>
<html lang="en" data-theme="<?= e(theme()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta name="theme-color" content="<?= theme() === 'light' ? '#f4f1ea' : '#0a0c10' ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e($p['name']) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e(SITE_URL) ?>/assets/img/og.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
  <link rel="preload" href="/assets/fonts/space-grotesk-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/jetbrains-mono-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
  <script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?php if (!empty($jsonld)): ?>
  <script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</head>
<body<?= !empty($rail) ? ' class="has-rail"' : '' ?>>
<a class="skip" href="#main">Skip to content</a>
<div class="bg-grid" aria-hidden="true"></div>
<div class="scroll-progress" aria-hidden="true"></div>
<header class="site-header">
  <div class="header-inner">
    <a class="brand" href="/" aria-label="<?= e($p['name']) ?>, home"><span class="brand-mark" aria-hidden="true">IR</span><span class="brand-name">israel<span>.rocklyn</span></span></a>
    <p class="status-chip mono" title="<?= $green !== null ? 'Checked against the GitHub API ' . e(ago(date('c', $live['checked']))) : 'Open to opportunities' ?>">
      <span class="dot" aria-hidden="true"></span>
<?php if ($green !== null): ?>
      <span>CI green on <?= $green ?>/<?= count(projects()) ?> repos</span>
<?php else: ?>
      <span>available for hire</span>
<?php endif; ?>
    </p>
    <nav id="site-nav" class="site-nav" aria-label="Main">
      <a href="/#work">Work</a>
      <a href="/#capabilities">Capabilities</a>
      <a href="/#notes">Notes</a>
      <a href="/#shell">Shell</a>
      <a href="/resume">Résumé</a>
      <a href="/#contact" class="nav-cta">Contact</a>
    </nav>
    <div class="header-tools">
      <button class="icon-btn mono" type="button" data-palette-open aria-label="Open command palette (Ctrl+K)"><kbd>Ctrl</kbd><kbd>K</kbd></button>
      <button class="icon-btn" type="button" data-theme-toggle aria-label="Switch colour theme">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 3a9 9 0 1 0 9 9 7 7 0 0 1-9-9Z"/></svg>
      </button>
      <button class="icon-btn menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Menu">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
      </button>
    </div>
  </div>
</header>
<?php if (!empty($rail)): ?>
<nav class="rail mono" aria-label="Sections">
<?php $i = 0; foreach ($sections as $id => $label): ?>
  <a href="#<?= e($id) ?>" data-rail="<?= e($id) ?>"><span><?= sprintf('%02d', $i++) ?></span><em><?= e($label) ?></em></a>
<?php endforeach; ?>
</nav>
<?php endif; ?>
<main id="main">
