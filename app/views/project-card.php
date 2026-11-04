<?php
/** A compact project card for the grid. @var string $slug @var array $project */
$lead = $project['results'][0];
?>
<article class="card" data-area="<?= e($project['area']) ?>">
  <a class="card-link" href="/work/<?= e($slug) ?>">
<?php if ($project['image']): ?>
    <img class="card-img" src="/assets/img/<?= e($project['image']) ?>" alt="" width="1280" height="800" loading="lazy" decoding="async">
<?php else: ?>
    <div class="card-img card-cover" aria-hidden="true"><span><?= e($lead[0]) ?></span></div>
<?php endif; ?>
    <h3><?= e($project['title']) ?></h3>
  </a>
  <p><?= e($project['tagline']) ?></p>
  <p class="card-metric"><strong><?= e($lead[0]) ?></strong> <?= e($lead[1]) ?></p>
  <p class="tags"><?= e(implode(' · ', array_slice($project['stack'], 0, 4))) ?></p>
</article>
