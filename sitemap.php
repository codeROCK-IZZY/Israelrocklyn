<?php
/** /sitemap.xml (rewritten here by .htaccess), generated from the project catalogue. */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$urls = ['/'];
foreach (array_keys(projects()) as $slug) {
    $urls[] = '/work/' . $slug;
}
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as $u) {
    echo '  <url><loc>', e(SITE_URL . $u), '</loc></url>', "\n";
}
echo '</urlset>', "\n";
