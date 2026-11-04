<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$p = profile();
$projects = projects();
$featured = array_filter($projects, static fn (array $x): bool => $x['featured']);
$more = array_filter($projects, static fn (array $x): bool => !$x['featured']);

$config = config();
$formReady = $config['recipient'] !== '' && $config['secret'] !== '';
$token = $formReady ? contact_token($config['secret']) : '';
$status = (string) ($_GET['contact'] ?? '');
$statusMessage = CONTACT_MESSAGES[$status] ?? null;

send_page_headers();
header('Cache-Control: no-cache'); // the form token is time-stamped

view('head', [
    'title' => $p['name'] . ' · ' . $p['headline'],
    'description' => 'Israel Rocklyn, AI & Cloud Software Engineer: AI systems with measured results (RAG with citations, LLM agents, ML models) and the cloud platforms they run on. 10 projects with tests, CI and evaluations.',
    'path' => '/',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $p['name'],
        'jobTitle' => $p['headline'],
        'url' => SITE_URL . '/',
        'sameAs' => [$p['github']],
        'knowsAbout' => ['Artificial intelligence', 'Machine learning', 'Large language models', 'MLOps', 'Cloud computing', 'AWS', 'Kubernetes', 'WordPress', 'SEO'],
        'hasCredential' => array_map(static fn (array $ed): array => [
            '@type' => 'EducationalOccupationalCredential', 'credentialCategory' => 'degree', 'name' => $ed['degree'] . ', ' . $ed['focus'],
        ], $p['education']),
    ],
]);
?>
<section class="hero wrap" aria-labelledby="hero-title">
  <p class="eyebrow"><?= e($p['headline']) ?> · Open to opportunities</p>
  <h1 id="hero-title">I build AI systems that <em>prove</em> they work, and the cloud platforms they run on.</h1>
  <p class="lede"><?= e($p['summary']) ?></p>
  <div class="hero-actions">
    <a class="button" href="#work">See the work</a>
    <a class="button button-quiet" href="#contact">Get in touch</a>
  </div>
  <dl class="facts">
    <div><dt>Education</dt><dd>M.S. Computer Science (AI) · B.Sc. Information Systems</dd></div>
    <div><dt>Location</dt><dd><?= e($p['location']) ?></dd></div>
    <div><dt>Shipped</dt><dd>10 projects, each with tests, CI and an evaluation</dd></div>
  </dl>
</section>

<section class="proof" aria-label="Highlights">
  <div class="wrap proof-grid">
    <p><strong>92%</strong> grounded-answer accuracy, citing page, line or timestamp</p>
    <p><strong>0</strong> errors when a server is killed under load</p>
    <p><strong>93%</strong> of fraud dollars caught at about 31 alerts a day</p>
    <p><strong>96.9%</strong> correct outcomes for AI agents, with human approval</p>
  </div>
</section>

<section id="work" class="section wrap" aria-labelledby="work-title">
  <header class="section-head">
    <p class="section-label">01 · Selected work</p>
    <h2 id="work-title">Four systems, start to finish</h2>
    <p class="muted">Each one solves a stated problem, ships with tests and CI, and reports its numbers, including the ones that came out badly.</p>
  </header>

<?php $i = 0; foreach ($featured as $slug => $x): $i++; ?>
  <article class="feature<?= $i % 2 === 0 ? ' feature-flip' : '' ?>">
    <a class="feature-img" href="/work/<?= e($slug) ?>" tabindex="-1" aria-hidden="true">
      <img src="/assets/img/<?= e($x['image']) ?>" alt="" width="1280" height="800"<?= $i > 1 ? ' loading="lazy"' : '' ?> decoding="async">
    </a>
    <div class="feature-text">
      <p class="kicker"><?= e(['ai' => 'AI & LLM', 'ml' => 'Machine learning', 'platform' => 'Cloud & platform'][$x['area']]) ?></p>
      <h3><a href="/work/<?= e($slug) ?>"><?= e($x['title']) ?></a></h3>
      <p><?= e($x['tagline']) ?></p>
      <ul class="results">
<?php foreach ($x['results'] as [$value, $label]): ?>
        <li><strong><?= e($value) ?></strong> <span><?= e($label) ?></span></li>
<?php endforeach; ?>
      </ul>
      <p class="tags"><?= e(implode(' · ', $x['stack'])) ?></p>
      <p class="links"><a href="/work/<?= e($slug) ?>">Read the case study</a> <a href="<?= e($x['repo']) ?>" rel="noopener">Code on GitHub</a></p>
    </div>
  </article>
<?php endforeach; ?>

  <div class="more-head">
    <h3>More projects</h3>
    <div class="filters" role="group" aria-label="Filter projects">
      <button type="button" class="chip" data-filter="all" aria-pressed="true">All</button>
      <button type="button" class="chip" data-filter="ai" aria-pressed="false">AI &amp; LLM</button>
      <button type="button" class="chip" data-filter="ml" aria-pressed="false">Machine learning</button>
      <button type="button" class="chip" data-filter="platform" aria-pressed="false">Cloud &amp; platform</button>
    </div>
  </div>
  <div class="cards">
<?php foreach ($more as $slug => $project) { view('project-card', ['slug' => $slug, 'project' => $project]); } ?>
  </div>
</section>

<section id="approach" class="section section-tint" aria-labelledby="approach-title">
  <div class="wrap">
    <header class="section-head">
      <p class="section-label">02 · Approach</p>
      <h2 id="approach-title">How I build</h2>
    </header>
    <ol class="principles">
<?php foreach ($p['principles'] as $pr): ?>
      <li><h3><?= e($pr['title']) ?></h3><p><?= e($pr['text']) ?></p></li>
<?php endforeach; ?>
    </ol>
  </div>
</section>

<section id="about" class="section wrap" aria-labelledby="about-title">
  <header class="section-head">
    <p class="section-label">03 · About</p>
    <h2 id="about-title">Engineer first, with an eye on the business</h2>
  </header>
  <div class="about-grid">
    <div>
      <p class="lede-sm">I work across the whole path of an AI product: data and models, the API around them, the interface people use, and the cloud platform underneath. My Information Systems background means projects start from a business problem; my Computer Science master's, with a concentration in AI, means the models are chosen and evaluated with care.</p>
      <h3 class="small-head">Roles I'm open to</h3>
      <p class="roles"><?= e(implode(' · ', $p['roles'])) ?></p>
    </div>
    <div class="education">
<?php foreach ($p['education'] as $ed): ?>
      <article>
        <h3><?= e($ed['degree']) ?></h3>
        <p class="kicker"><?= e($ed['focus']) ?></p>
        <p class="muted"><?= e($ed['note']) ?></p>
      </article>
<?php endforeach; ?>
    </div>
  </div>
  <div class="skills">
<?php foreach ($p['skills'] as $group => $items): ?>
    <div>
      <h3 class="small-head"><?= e($group) ?></h3>
      <ul><?php foreach ($items as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ul>
    </div>
<?php endforeach; ?>
  </div>
</section>

<section id="services" class="section section-tint" aria-labelledby="services-title">
  <div class="wrap">
    <header class="section-head">
      <p class="section-label">04 · Web &amp; growth</p>
      <h2 id="services-title">Websites and digital marketing</h2>
      <p class="muted">Besides engineering AI systems, I build and grow websites, so I can ship a product and help it reach the right people.</p>
    </header>
    <div class="services">
<?php foreach ($p['services'] as $sv): ?>
      <article><h3><?= e($sv['title']) ?></h3><p><?= e($sv['text']) ?></p></article>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section id="contact" class="section wrap contact" aria-labelledby="contact-title">
  <header class="section-head">
    <p class="section-label">05 · Contact</p>
    <h2 id="contact-title">Let's build something</h2>
    <p class="muted">Hiring for AI, ML, cloud or full-stack work, or need a website that brings in customers? Send a message and I'll reply by email.</p>
  </header>
  <div class="contact-grid">
<?php if ($formReady): ?>
    <form class="contact-form" action="/contact.php" method="post" novalidate>
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
      <h3 class="small-head">Elsewhere</h3>
      <p><a href="<?= e($p['github']) ?>" rel="me noopener">GitHub · codeROCK-IZZY</a></p>
      <h3 class="small-head">Good to know</h3>
      <p class="muted">Available for hire, based in the <?= e($p['location']) ?>. Messages go straight to my inbox; your address is used only to reply.</p>
    </aside>
  </div>
</section>
<?php view('foot'); ?>
