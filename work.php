<?php
/** A project case study: /work/<slug> (rewritten to work.php?slug=<slug> by .htaccess). */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$slug = (string) ($_GET['slug'] ?? '');
$projects = projects();
if (!preg_match('/^[a-z0-9-]{1,64}$/', $slug) || !isset($projects[$slug])) {
    require __DIR__ . '/404.php';
    exit;
}
$x = $projects[$slug];
$slugs = array_keys($projects);
$next = $slugs[(array_search($slug, $slugs, true) + 1) % count($slugs)];
$area = ['ai' => 'AI & LLM', 'ml' => 'Machine learning', 'platform' => 'Cloud & platform'][$x['area']];
$related = array_filter(notes(), static fn (array $n): bool => $n['project'] === $slug);
$live = live_status()['repos'][$slug] ?? null;

send_page_headers();
header('Cache-Control: public, max-age=600');

view('head', [
    'title' => $x['title'] . ' · Case study · Israel Rocklyn',
    'description' => $x['tagline'] . ' ' . $x['results'][0][0] . ' ' . $x['results'][0][1] . '.',
    'path' => '/work/' . $slug,
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareSourceCode',
        'name' => $x['title'],
        'description' => $x['tagline'],
        'codeRepository' => $x['repo'],
        'programmingLanguage' => $x['stack'],
        'author' => ['@type' => 'Person', 'name' => profile()['name'], 'url' => SITE_URL . '/'],
    ],
]);
?>
<article class="case wrap">
  <nav class="crumbs mono" aria-label="Breadcrumb"><a href="/#work">work</a> <span aria-hidden="true">/</span> <span><?= e($slug) ?></span></nav>
  <header class="case-head">
    <p class="kicker mono"><?= e($area) ?> · <?= e($x['hop']['host']) ?><?php view('ci-badge', ['slug' => $slug]); ?></p>
    <h1><?= e($x['title']) ?></h1>
    <p class="lede"><?= e($x['tagline']) ?></p>
    <p class="hero-actions">
      <a class="button" href="<?= e($x['repo']) ?>" rel="noopener">View the code</a>
<?php if (demo_shots($slug)): ?>
      <a class="button button-ghost" href="#demo">Demo tour</a>
<?php endif; ?>
      <a class="button button-ghost mono" href="#run">Run it ›_</a>
<?php if ($live && $live['pushed'] !== ''): ?>
      <span class="muted mono small">last push <?= e(ago($live['pushed'])) ?></span>
<?php endif; ?>
    </p>
  </header>

  <section class="case-results" aria-label="Results">
<?php foreach ($x['results'] as [$value, $label]): ?>
    <p><strong class="mono"><?= e($value) ?></strong><span><?= e($label) ?></span></p>
<?php endforeach; ?>
  </section>

<?php if ($x['image']): ?>
  <figure class="case-figure">
    <img src="/assets/img/<?= e($x['image']) ?>" alt="<?= e($x['image_alt']) ?>" width="1280" height="800" decoding="async">
    <figcaption class="muted small"><?= e($x['image_alt']) ?>. Screenshot from the project's demo (synthetic data).</figcaption>
  </figure>
<?php endif; ?>

<?php $shots = demo_shots($slug); if ($shots): ?>
  <section class="case-section case-wide" id="demo" aria-labelledby="demo-title">
    <h2 id="demo-title"><span class="mono">demo</span> Demo tour</h2>
    <p class="muted">Screenshots from the running application, with its synthetic demo data. Select one to enlarge.</p>
    <div class="shots" data-gallery>
<?php foreach ($shots as $i => $shot): ?>
      <figure class="shot">
        <a href="<?= e($shot['src']) ?>" data-shot="<?= $i ?>"><img src="<?= e($shot['src']) ?>" alt="<?= e($shot['caption']) ?>" loading="lazy" decoding="async" width="1440" height="900"></a>
        <figcaption><?= e($shot['caption']) ?></figcaption>
      </figure>
<?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

  <section class="case-section">
    <h2><span class="mono">01</span> The problem</h2>
    <p><?= e($x['problem']) ?></p>
  </section>

  <section class="case-section">
    <h2><span class="mono">02</span> How it works</h2>
    <ol class="flow mono" aria-label="Architecture, step by step">
<?php foreach ($x['flow'] as $step): ?>
      <li><?= e($step) ?></li>
<?php endforeach; ?>
    </ol>
  </section>

  <section class="case-section">
    <h2><span class="mono">03</span> What I built</h2>
    <ul class="checklist">
<?php foreach ($x['built'] as $b): ?>
      <li><?= e($b) ?></li>
<?php endforeach; ?>
    </ul>
  </section>

  <section class="case-section">
    <h2><span class="mono">04</span> What I learned</h2>
<?php foreach ($x['lessons'] as $l): ?>
    <p class="lesson"><?= e($l) ?></p>
<?php endforeach; ?>
  </section>

  <section class="case-section">
    <h2><span class="mono">05</span> Known limitations</h2>
    <p class="muted">Every repository documents what it doesn't do yet. The main ones:</p>
    <ul class="checklist checklist-muted">
<?php foreach ($x['limits'] as $l): ?>
      <li><?= e($l) ?></li>
<?php endforeach; ?>
    </ul>
  </section>

  <section class="case-section">
    <h2><span class="mono">06</span> Stack</h2>
    <p class="stack-list"><?php foreach ($x['stack'] as $s): ?><span><?= e($s) ?></span><?php endforeach; ?></p>
    <p class="muted small">The repository has the design document, operations runbook, full evaluation report and known limitations. All data is synthetic, generated by code in the repository.</p>
  </section>

  <section class="case-section" id="run" aria-labelledby="run-title">
    <h2 id="run-title"><span class="mono">run</span> Run it yourself</h2>
    <p class="muted">Every project runs on a laptop: no cloud account and no paid API needed.</p>
    <div class="codeblock">
      <button class="copy-btn mono" type="button" data-copy aria-label="Copy commands">copy</button>
<pre><code>git clone <?= e($x['repo']) ?>.git
cd <?= e($slug) ?>

<?= e(implode("
", $x['run'])) ?></code></pre>
    </div>
<?php if ($x['local_url'] !== ''): ?>
    <p class="muted small">Then open <code><?= e($x['local_url']) ?></code>. The README has the demo logins and everything else.</p>
<?php endif; ?>
  </section>

<?php if ($related): ?>
  <section class="case-section">
    <h2><span class="mono">07</span> Notes from this project</h2>
<?php foreach ($related as $ns => $note): ?>
    <p><a href="/notes/<?= e($ns) ?>"><?= e($note['title']) ?></a> <span class="muted">· <?= e($note['summary']) ?></span></p>
<?php endforeach; ?>
  </section>
<?php endif; ?>

  <nav class="case-next" aria-label="Next project">
    <p class="mono muted small">next project</p>
    <a href="/work/<?= e($next) ?>"><?= e($projects[$next]['title']) ?> →</a>
  </nav>
</article>
<?php view('foot'); ?>
