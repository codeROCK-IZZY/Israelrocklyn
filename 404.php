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
  <p class="hero-meta mono"><span class="tag tag-error">404</span> trace-request: destination unreachable</p>
  <h1 class="hero-name">No route to <span>host</span></h1>
  <p class="lede">The link may be old or mistyped. Press <kbd>Ctrl</kbd> <kbd>K</kbd> to search, or head back.</p>
  <div class="hero-actions">
    <a class="button" href="/#work">See the work</a>
    <a class="button button-ghost" href="/">Home</a>
  </div>
</section>
<?php view('foot'); ?>
