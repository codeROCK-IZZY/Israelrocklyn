<?php $p = profile(); ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-inner">
    <div>
      <p class="brand"><span class="brand-mark" aria-hidden="true">IR</span><span class="brand-name">israel<span>.rocklyn</span></span></p>
      <p class="muted"><?= e($p['headline']) ?> · <?= e($p['location']) ?></p>
    </div>
    <ul class="footer-links mono">
      <li><a href="<?= e($p['github']) ?>" rel="me noopener">github</a></li>
      <li><a href="/resume">résumé</a></li>
      <li><a href="/#notes">notes</a></li>
      <li><a href="/#contact">contact</a></li>
      <li><a href="/sitemap.xml">sitemap</a></li>
    </ul>
    <p class="muted small">© <?= date('Y') ?> <span data-owner><?= e($p['name']) ?></span>. Built with plain PHP, CSS and a little JavaScript; no third-party trackers and no cookies except your theme choice; visits are counted anonymously on this server, and not at all if your browser asks not to be tracked. Project data is synthetic; every number comes from the committed evaluation in its repository.</p>
  </div>
</footer>

<dialog class="palette" aria-label="Command palette">
  <form method="dialog" class="palette-inner">
    <label class="sr-only" for="palette-input">Search pages, projects and actions</label>
    <input id="palette-input" type="text" placeholder="Jump to a project, note or section…" autocomplete="off" spellcheck="false">
    <ul class="palette-list" role="listbox" aria-label="Results"></ul>
    <p class="palette-hint mono">↑↓ to move · Enter to open · Esc to close</p>
  </form>
</dialog>
<dialog class="lightbox" aria-label="Screenshot viewer">
  <figure>
    <figcaption></figcaption>
  </figure>
  <div class="lightbox-nav mono">
    <button type="button" data-lb="prev" aria-label="Previous screenshot">←</button>
    <span data-lb-count></span>
    <button type="button" data-lb="next" aria-label="Next screenshot">→</button>
    <button type="button" data-lb="close" aria-label="Close">esc</button>
  </div>
</dialog>
<script type="application/json" id="site-data"><?= json_encode(site_data(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</body>
</html>
