<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$p = profile();
$projects = projects();
$featured = array_filter($projects, static fn (array $x): bool => $x['featured']);
$live = live_status();
$lastPush = null; // [slug, ISO time] of the most recent push across the project repos
foreach ($live['repos'] ?? [] as $slug => $r) {
    if (isset($projects[$slug]) && ($r['pushed'] ?? '') !== '' && ($lastPush === null || $r['pushed'] > $lastPush[1])) {
        $lastPush = [$slug, $r['pushed']];
    }
}

$config = config();
$formReady = $config['recipient'] !== '' && $config['secret'] !== '';
$token = $formReady ? contact_token($config['secret']) : '';
$status = (string) ($_GET['contact'] ?? '');
$statusMessage = CONTACT_MESSAGES[$status] ?? null;
$portrait = is_file(__DIR__ . '/assets/img/portrait.webp');
$areaNames = ['ai' => 'AI & LLM', 'ml' => 'Machine learning', 'platform' => 'Cloud & platform'];

send_page_headers();
header('Cache-Control: no-cache'); // the form token is time-stamped

view('head', [
    'title' => $p['name'] . ' · ' . $p['headline'],
    'description' => 'Israel Rocklyn, AI, ML & Cloud Engineer: 10 production-grade AI and cloud systems with tests, CI and measured results. RAG with citations, LLM agents with human approval, MLOps, model serving on Kubernetes, AWS.',
    'path' => '/',
    'rail' => true,
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $p['name'],
        'jobTitle' => $p['headline'],
        'url' => SITE_URL . '/',
        'sameAs' => [$p['github']],
        'knowsAbout' => array_keys($p['capabilities']),
        'hasCredential' => array_map(static fn (array $ed): array => [
            '@type' => 'EducationalOccupationalCredential', 'credentialCategory' => 'degree', 'name' => $ed['degree'] . ', ' . $ed['focus'],
        ], $p['education']),
    ],
]);
?>
<section id="top" class="hero wrap" aria-labelledby="hero-name" data-section>
  <p class="hero-meta mono"><span class="tag">hop 00</span> you → israelrocklyn.info · <?= e($p['headline']) ?> · <?= e($p['location']) ?></p>
  <h1 id="hero-name" class="hero-name">Israel <span>Rocklyn</span></h1>

  <div class="split">
    <div class="split-side split-left">
      <p class="split-word">engineer</p>
      <p class="split-text">AI systems that prove they work: answers with citations, agents with human approval, models with honest evaluation.</p>
    </div>
    <div class="portrait-stage" data-portrait>
      <figure class="portrait<?= $portrait ? '' : ' portrait-placeholder' ?>">
        <span class="portrait-grid" aria-hidden="true"></span>
<?php if ($portrait): ?>
        <img src="<?= e(asset('img/portrait.webp')) ?>" alt="Portrait of <?= e($p['name']) ?>" width="900" height="1000" fetchpriority="high">
        <span class="portrait-scan" aria-hidden="true"></span>
<?php else: ?>
        <div class="portrait-art" aria-hidden="true"><span class="half half-paint"></span><span class="half half-wire"></span><span class="mono-mark">IR</span></div>
<?php endif; ?>
      </figure>
      <ul class="orbit mono" aria-hidden="true">
        <li class="o1">LLMs</li>
        <li class="o2">AWS</li>
        <li class="o3">Kubernetes</li>
        <li class="o4">MLOps</li>
      </ul>
    </div>
    <div class="split-side split-right">
      <p class="split-word mono">&lt;builder/&gt;</p>
      <p class="split-text">The cloud platforms they run on: AWS foundations, MLOps pipelines and model serving on Kubernetes.</p>
    </div>
  </div>

  <div class="hero-foot">
    <p class="lede"><?= e($p['summary']) ?></p>
    <div class="hero-actions">
      <a class="button" href="#trace">Trace a request <span aria-hidden="true">↓</span></a>
      <a class="button button-ghost mono" href="#shell">Open a shell <span aria-hidden="true">›_</span></a>
      <a class="text-link" href="/resume">Résumé</a>
    </div>
  </div>

  <div class="stats" data-count>
    <p><strong>10</strong><span>production-grade systems, each with tests, CI and an evaluation</span></p>
    <p><strong>92%</strong><span>grounded answers that cite page, line or timestamp</span></p>
    <p><strong>0</strong><span>errors when a server is killed under load</span></p>
    <p><strong>93%</strong><span>of fraud dollars caught at about 31 alerts a day</span></p>
  </div>

  <p class="now mono"><span class="tag">now</span> building 5 new AWS projects and 8 AI systems, added here as each one ships<?php if ($lastPush !== null): ?> <span class="muted">· last push: <a href="/work/<?= e($lastPush[0]) ?>"><?= e($projects[$lastPush[0]]['title']) ?></a>, <time datetime="<?= e($lastPush[1]) ?>"><?= e(ago($lastPush[1])) ?></time></span><?php endif; ?></p>
</section>

<section id="trace" class="section wrap" aria-labelledby="trace-title" data-section>
  <header class="section-head">
    <p class="eyebrow mono">01 · trace</p>
    <h2 id="trace-title">Trace a request through ten systems I built</h2>
    <p class="muted">An AI request touches every layer: the gateway that admits it, the retrieval that grounds it, the model that answers, the agents that act, the monitoring that watches and the cloud underneath. Each hop below is one of my projects, with its measured result instead of a latency.</p>
  </header>
  <div class="trace-panel" data-trace>
    <div class="trace-head mono">
      <span>trace-request --to israelrocklyn.info</span>
      <span class="muted">10 hops max · results from each repo's evaluation</span>
    </div>
    <ol class="trace-list">
      <li class="trace-hop is-origin"><span class="hop-n mono">0</span><span class="hop-host mono">you</span><span class="hop-role">where the request starts</span><span class="hop-val mono">0 ms</span></li>
<?php $n = 0; foreach (trace_order() as $slug): $x = $projects[$slug]; $n++; ?>
      <li class="trace-hop">
        <span class="hop-n mono"><?= $n ?></span>
        <a class="hop-host mono" href="/work/<?= e($slug) ?>"><?= e($x['hop']['host']) ?></a>
        <span class="hop-role"><?= e($x['hop']['role']) ?> <span class="muted">· <?= e($x['title']) ?></span></span>
        <span class="hop-val mono"><?= e($x['hop']['value']) ?><small><?= e($x['hop']['label']) ?></small></span>
      </li>
<?php endforeach; ?>
    </ol>
  </div>
</section>

<section id="work" class="section wrap" aria-labelledby="work-title" data-section>
  <header class="section-head">
    <p class="eyebrow mono">02 · work</p>
    <h2 id="work-title">Selected work</h2>
    <p class="muted">Every project solves a stated problem, ships with tests and CI, and reports its numbers, including the ones that came out badly.<?= $live ? ' Build status is checked live against GitHub.' : '' ?></p>
  </header>
<?php $i = 0; foreach ($featured as $slug => $x): $i++; ?>
  <article class="feature<?= $i % 2 === 0 ? ' feature-flip' : '' ?>">
    <a class="feature-img" href="/work/<?= e($slug) ?>" tabindex="-1" aria-hidden="true">
      <img src="/assets/img/<?= e($x['image']) ?>" alt="" width="1280" height="800" loading="lazy" decoding="async">
    </a>
    <div class="feature-text">
      <p class="kicker mono"><?= e($areaNames[$x['area']]) ?><?php view('ci-badge', ['slug' => $slug]); ?></p>
      <h3><a href="/work/<?= e($slug) ?>"><?= e($x['title']) ?></a></h3>
      <p><?= e($x['tagline']) ?></p>
      <ul class="results">
<?php foreach ($x['results'] as [$value, $label]): ?>
        <li><strong class="mono"><?= e($value) ?></strong><span><?= e($label) ?></span></li>
<?php endforeach; ?>
      </ul>
      <p class="tags mono"><?= e(implode(' · ', $x['stack'])) ?></p>
      <p class="links"><a href="/work/<?= e($slug) ?>">Case study →</a><?php if (demo_shots($slug)): ?> <a href="/work/<?= e($slug) ?>#demo">Demo tour</a><?php endif; ?> <a href="<?= e($x['repo']) ?>" rel="noopener">Code</a></p>
    </div>
  </article>
<?php endforeach; ?>

  <div class="more-head">
    <h3>All projects</h3>
    <div class="filters" role="group" aria-label="Filter projects">
      <button type="button" class="chip" data-filter="all" aria-pressed="true">All</button>
<?php foreach ($areaNames as $k => $label): ?>
      <button type="button" class="chip" data-filter="<?= e($k) ?>" aria-pressed="false"><?= e($label) ?></button>
<?php endforeach; ?>
    </div>
  </div>
  <div class="cards">
<?php foreach ($projects as $slug => $project) { view('project-card', ['slug' => $slug, 'project' => $project]); } ?>
  </div>
</section>

<section id="capabilities" class="section wrap" aria-labelledby="cap-title" data-section>
  <header class="section-head">
    <p class="eyebrow mono">03 · capabilities</p>
    <h2 id="cap-title">Skills, with the evidence</h2>
    <p class="muted">Not a wall of logos: each capability links to the projects that prove it.</p>
  </header>
  <div class="capabilities">
<?php foreach ($p['capabilities'] as $cap => $slugs): ?>
    <div class="capability">
      <h3><?= e($cap) ?></h3>
      <p class="proof-count mono"><?= count($slugs) ?> project<?= count($slugs) > 1 ? 's' : '' ?></p>
      <ul>
<?php foreach ($slugs as $s): ?>
        <li><a href="/work/<?= e($s) ?>"><?= e($projects[$s]['title']) ?></a></li>
<?php endforeach; ?>
      </ul>
    </div>
<?php endforeach; ?>
  </div>

  <div class="two-col">
    <div>
      <h3 class="small-head mono">how I build</h3>
      <ol class="principles">
<?php foreach ($p['principles'] as $pr): ?>
        <li><strong><?= e($pr['title']) ?></strong> <?= e($pr['text']) ?></li>
<?php endforeach; ?>
      </ol>
    </div>
    <div>
      <h3 class="small-head mono">education</h3>
<?php foreach ($p['education'] as $ed): ?>
      <div class="edu">
        <p class="edu-degree"><?= e($ed['degree']) ?></p>
        <p class="kicker mono"><?= e($ed['focus']) ?></p>
        <p class="muted"><?= e($ed['note']) ?></p>
      </div>
<?php endforeach; ?>
      <h3 class="small-head mono">open to</h3>
      <p class="roles"><?= e(implode(' · ', $p['roles'])) ?></p>
    </div>
  </div>

  <div class="stack-groups">
<?php foreach ($p['skills'] as $group => $items): ?>
    <div>
      <h3 class="small-head mono"><?= e(strtolower($group)) ?></h3>
      <p class="stack-list"><?php foreach ($items as $s): ?><span><?= e($s) ?></span><?php endforeach; ?></p>
    </div>
<?php endforeach; ?>
  </div>
</section>

<section id="notes" class="section wrap" aria-labelledby="notes-title" data-section>
  <header class="section-head">
    <p class="eyebrow mono">04 · notes</p>
    <h2 id="notes-title">Engineering notes</h2>
    <p class="muted">Short write-ups of real problems from these projects: what broke, how I measured it, and the fix.</p>
  </header>
  <div class="notes">
<?php foreach (notes() as $slug => $note): ?>
    <a class="note-card" href="/notes/<?= e($slug) ?>">
      <p class="mono muted small"><?= e(date('M j, Y', (int) strtotime($note['date']))) ?> · <?= e($projects[$note['project']]['title']) ?></p>
      <h3><?= e($note['title']) ?></h3>
      <p class="muted"><?= e($note['summary']) ?></p>
      <span class="text-link">Read →</span>
    </a>
<?php endforeach; ?>
  </div>
</section>

<section id="shell" class="section wrap" aria-labelledby="shell-title" data-section>
  <header class="section-head">
    <p class="eyebrow mono">05 · shell</p>
    <h2 id="shell-title">Prefer a terminal?</h2>
    <p class="muted">Explore this portfolio from the command line. Try <code>help</code>, <code>ls</code>, <code>trace</code> or <code>open fraud-detection-engine</code>.</p>
  </header>
  <div class="terminal" data-shell>
    <div class="terminal-bar mono"><span class="lights" aria-hidden="true"><i></i><i></i><i></i></span><span>guest@israelrocklyn.info: ~</span></div>
    <div class="terminal-body mono">
      <div class="terminal-out" aria-live="polite"><noscript><p>The shell needs JavaScript. Everything it shows is also on this page.</p></noscript></div>
      <form class="terminal-line" autocomplete="off">
        <label for="shell-input" class="prompt">guest@ir:~$</label>
        <input id="shell-input" type="text" spellcheck="false" autocapitalize="off">
      </form>
    </div>
  </div>
</section>

<section id="services" class="section wrap" aria-labelledby="services-title" data-section>
  <header class="section-head">
    <p class="eyebrow mono">06 · web &amp; growth</p>
    <h2 id="services-title">Websites and digital marketing</h2>
    <p class="muted">Besides engineering AI systems, I build and grow websites, so a product can also reach the right people.</p>
  </header>
  <div class="services">
<?php foreach ($p['services'] as $sv): ?>
    <article><h3><?= e($sv['title']) ?></h3><p><?= e($sv['text']) ?></p></article>
<?php endforeach; ?>
  </div>
</section>

<section id="contact" class="section wrap contact" aria-labelledby="contact-title" data-section>
  <header class="section-head">
    <p class="eyebrow mono">07 · contact</p>
    <h2 id="contact-title">Let's build something</h2>
    <p class="muted">Hiring for AI, ML, cloud or full-stack work, or need a website that brings in customers? Send a message and I'll reply by email.</p>
  </header>
  <div class="contact-grid">
<?php if ($formReady): ?>
    <form class="contact-form" action="/contact.php" method="post" novalidate>
      <div class="field-row">
        <div class="field">
          <label for="name">Name</label>
          <input id="name" name="name" type="text" autocomplete="name" required minlength="2" maxlength="100" aria-describedby="name-error">
          <p class="field-error" id="name-error" hidden></p>
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" autocomplete="email" required maxlength="254" aria-describedby="email-error">
          <p class="field-error" id="email-error" hidden></p>
        </div>
      </div>
      <div class="field">
        <label for="subject">Subject <span class="muted">(optional)</span></label>
        <input id="subject" name="subject" type="text" maxlength="150" aria-describedby="subject-error">
        <p class="field-error" id="subject-error" hidden></p>
      </div>
      <div class="field">
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="6" required minlength="20" maxlength="5000" aria-describedby="message-error"></textarea>
        <p class="field-error" id="message-error" hidden></p>
      </div>
      <div class="hp" aria-hidden="true">
        <label for="website">Leave this field empty</label>
        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
      </div>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <button class="button" type="submit">Send message</button>
      <p class="form-status<?= $statusMessage ? ($status === 'sent' ? ' is-ok' : ' is-error') : '' ?>" role="status" aria-live="polite"><?= e($statusMessage) ?></p>
    </form>
<?php else: ?>
    <p class="form-status is-error">The contact form is being set up. Please reach me through GitHub for now.</p>
<?php endif; ?>
    <aside class="contact-aside">
      <h3 class="small-head mono">elsewhere</h3>
      <p><a href="<?= e($p['github']) ?>" rel="me noopener">github.com/codeROCK-IZZY</a></p>
      <p><a href="/resume">Printable résumé</a></p>
      <h3 class="small-head mono">good to know</h3>
      <p class="muted">Available for hire, based in the <?= e($p['location']) ?>. Messages go straight to my inbox; your address is used only to reply.</p>
    </aside>
  </div>
</section>
<?php view('foot'); ?>
