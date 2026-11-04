<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

send_page_headers(404);
view('head', [
    'title' => 'Page not found · Israel Rocklyn',
    'description' => 'This page does not exist.',
    'path' => '/404',
]);
?>
<section class="hero wrap notfound">
  <p class="eyebrow">404</p>
  <h1>This page doesn't <em>exist</em>.</h1>
  <p class="lede">The link may be old or mistyped. The work is all on the home page.</p>
  <div class="hero-actions">
    <a class="button" href="/#work">See the work</a>
    <a class="button button-quiet" href="/">Home</a>
  </div>
</section>
<?php view('foot'); ?>
