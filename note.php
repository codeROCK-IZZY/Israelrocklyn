<?php
/** An engineering note: /notes/<slug> (rewritten to note.php?slug=<slug> by .htaccess). */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$slug = (string) ($_GET['slug'] ?? '');
$all = notes();
if (!preg_match('/^[a-z0-9-]{1,80}$/', $slug) || !isset($all[$slug])) {
    require __DIR__ . '/404.php';
    exit;
}
$n = $all[$slug];
$project = projects()[$n['project']];

send_page_headers();
header('Cache-Control: public, max-age=3600');

view('head', [
    'title' => $n['title'] . ' · Israel Rocklyn',
    'description' => $n['summary'],
    'path' => '/notes/' . $slug,
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'TechArticle',
        'headline' => $n['title'],
        'description' => $n['summary'],
        'datePublished' => $n['date'],
        'author' => ['@type' => 'Person', 'name' => profile()['name'], 'url' => SITE_URL . '/'],
    ],
]);
?>
<article class="note wrap">
  <nav class="crumbs mono" aria-label="Breadcrumb"><a href="/#notes">notes</a> <span aria-hidden="true">/</span> <span><?= e($slug) ?></span></nav>
  <header>
    <p class="mono muted small"><?= e(date('F j, Y', (int) strtotime($n['date']))) ?> · from <a href="/work/<?= e($n['project']) ?>"><?= e($project['title']) ?></a></p>
    <h1><?= e($n['title']) ?></h1>
    <p class="lede"><?= e($n['summary']) ?></p>
  </header>
  <div class="prose">
<?php foreach ($n['body'] as [$type, $content]): ?>
<?php if ($type === 'h'): ?>
    <h2><?= e($content) ?></h2>
<?php elseif ($type === 'list'): ?>
    <ul><?php foreach ($content as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
<?php elseif ($type === 'code'): ?>
    <pre><code><?= e($content) ?></code></pre>
<?php else: ?>
    <p><?= e($content) ?></p>
<?php endif; ?>
<?php endforeach; ?>
  </div>
  <footer class="note-foot">
    <p>The full numbers, the code and the report are in the <a href="<?= e($project['repo']) ?>" rel="noopener"><?= e($project['title']) ?> repository</a>, and in the <a href="/work/<?= e($n['project']) ?>">case study</a>.</p>
  </footer>
</article>
<?php view('foot'); ?>
