<?php
/** A compact project card for the grid. @var string $slug @var array $project */
$lead = $project['results'][0];
?>
<article class="card" data-area="<?= e($project['area']) ?>">
  <a class="card-link" href="/work/<?= e($slug) ?>">
<?php if ($project['image']): ?>
    <img class="card-img" src="/assets/img/<?= e($project['image']) ?>" alt="" width="1280" height="800" loading="lazy" decoding="async">
<?php else: ?>
    <div class="card-img card-cover" aria-hidden="true"><span class="mono"><?= e($lead[0]) ?></span></div>
<?php endif; ?>
    <h3><?= e($project['title']) ?></h3>
  </a>
  <p class="card-host mono"><?= e($project['hop']['host']) ?><?php view('ci-badge', ['slug' => $slug]); ?></p>
  <p><?= e($project['tagline']) ?></p>
  <p class="card-metric"><strong class="mono"><?= e($lead[0]) ?></strong> <?= e($lead[1]) ?></p>
<?php if (is_file(dirname(__DIR__, 2) . '/demo/' . $slug . '/index.html')): ?>
  <p class="card-demo"><a class="mono" href="/demo/<?= e($slug) ?>/">Open the live demo ›</a></p>
<?php endif; ?>
</article>
