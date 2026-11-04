<?php
/**
 * <head> and site header.
 * @var string $title @var string $description @var string $path
 * @var array|null $jsonld Structured data for this page (optional)
 */
$p = profile();
$canonical = SITE_URL . $path;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#f7f4ee" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#141311" media="(prefers-color-scheme: dark)">

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
  <link rel="preload" href="/assets/fonts/fraunces-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
  <script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?php if (!empty($jsonld)): ?>
  <script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="wordmark" href="/" aria-label="<?= e($p['name']) ?>, home">Israel <em>Rocklyn</em></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Menu</button>
    <nav id="site-nav" class="site-nav" aria-label="Main">
      <a href="/#work">Work</a>
      <a href="/#approach">Approach</a>
      <a href="/#about">About</a>
      <a href="/#services">Web &amp; growth</a>
      <a class="nav-cta" href="/#contact">Contact</a>
    </nav>
  </div>
</header>
<main id="main">
