<?php $p = profile(); ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-inner">
    <div>
      <p class="wordmark">Israel <em>Rocklyn</em></p>
      <p class="muted"><?= e($p['headline']) ?> · Based in the <?= e($p['location']) ?></p>
    </div>
    <ul class="footer-links">
      <li><a href="<?= e($p['github']) ?>" rel="me noopener">GitHub</a></li>
      <li><a href="/#contact">Contact</a></li>
      <li><a href="/sitemap.xml">Sitemap</a></li>
    </ul>
    <p class="muted small">© <?= date('Y') ?> <?= e($p['name']) ?>. All project data is synthetic; numbers come from each repository's committed evaluation.</p>
  </div>
</footer>
</body>
</html>
