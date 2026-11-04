<?php
/** /resume: a one-page résumé generated from the same data, designed to print or save as PDF. */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$p = profile();
send_page_headers();
header('Cache-Control: public, max-age=3600');

view('head', [
    'title' => 'Résumé · ' . $p['name'],
    'description' => 'Résumé of ' . $p['name'] . ', ' . $p['headline'] . ': education, capabilities and ten projects with measured results.',
    'path' => '/resume',
]);
?>
<article class="resume wrap">
  <header class="resume-head">
    <div>
      <h1><?= e($p['name']) ?></h1>
      <p class="lede"><?= e($p['headline']) ?> · <?= e($p['location']) ?></p>
    </div>
    <div class="resume-contact">
      <p class="mono small resume-links">israelrocklyn.info<br>github.com/codeROCK-IZZY</p>
      <img class="resume-qr" src="<?= e(asset('img/qr-site.svg')) ?>" alt="QR code linking to israelrocklyn.info" width="72" height="72">
    </div>
    <button class="button print-btn" type="button" data-print>Print or save as PDF</button>
  </header>

  <section>
    <h2 class="small-head mono">summary</h2>
    <p><?= e($p['summary']) ?></p>
  </section>

  <section>
    <h2 class="small-head mono">education</h2>
<?php foreach ($p['education'] as $ed): ?>
    <p><strong><?= e($ed['degree']) ?></strong> · <?= e($ed['focus']) ?></p>
<?php endforeach; ?>
  </section>

  <section>
    <h2 class="small-head mono">projects (all on GitHub, with tests, CI and evaluations)</h2>
<?php foreach (projects() as $slug => $x): ?>
    <div class="resume-project">
      <p><strong><?= e($x['title']) ?></strong> · <span class="muted"><?= e(implode(', ', array_slice($x['stack'], 0, 5))) ?></span></p>
      <p><?= e($x['tagline']) ?> <?= e(implode('; ', array_map(static fn (array $r): string => $r[0] . ' ' . $r[1], array_slice($x['results'], 0, 2)))) ?>.</p>
    </div>
<?php endforeach; ?>
  </section>

  <section>
    <h2 class="small-head mono">capabilities</h2>
    <p><?= e(implode(' · ', array_keys($p['capabilities']))) ?></p>
  </section>

  <section>
    <h2 class="small-head mono">technical skills</h2>
<?php foreach ($p['skills'] as $group => $items): ?>
    <p><strong><?= e($group) ?>:</strong> <?= e(implode(', ', $items)) ?></p>
<?php endforeach; ?>
  </section>

  <section>
    <h2 class="small-head mono">web &amp; digital marketing</h2>
    <p><?= e(implode(' · ', array_column($p['services'], 'title'))) ?></p>
  </section>
</article>
<?php view('foot'); ?>
