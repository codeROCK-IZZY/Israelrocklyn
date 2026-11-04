# israelrocklyn.info

The source of [israelrocklyn.info](https://israelrocklyn.info), the portfolio of Israel Rocklyn, AI, ML & Cloud
Engineer.

It is a PHP site with no framework, no database and no build step. Content lives in three PHP data
files, pages are rendered from shared templates, and every number on it comes from the evaluation
committed in the project's own repository.

| | |
|---|---|
| **Home** | split-portrait hero; a "trace a request" panel where each hop is a project with its measured result; selected work with live CI status; skills with the projects that prove them; engineering notes; an in-browser shell; services; contact |
| **Case studies** (`/work/<slug>`) | the problem, an architecture flow, what was built, lessons learned, known limitations, stack, related notes, live CI status and last push |
| **Engineering notes** (`/notes/<slug>`) | short write-ups of real problems from the projects (Little's law in model serving, false drift alarms, honest evaluation, thread spinning, abstention) |
| **Résumé** (`/resume`) | generated from the same data, with print styles for a clean PDF |
| **Live data** | each repository's latest GitHub Actions result and last push, fetched server-side in parallel and cached for 30 minutes ([app/lib/github.php](app/lib/github.php)) |
| **Interaction** | command palette (Ctrl/⌘ + K), a shell (`help`, `ls`, `cat`, `open`, `trace`, …), project filters, dark and light themes; all progressive enhancement, so every page works without JavaScript |
| **Content** | [app/data/profile.php](app/data/profile.php), [app/data/projects.php](app/data/projects.php), [app/data/notes.php](app/data/notes.php) |
| **Design** | dark technical layout with an amber accent; Space Grotesk, Inter and JetBrains Mono, self-hosted |
| **Hosting** | Apache + PHP 8.2 or later (20i Linux hosting) |

## Security and privacy

- **No secrets in the repository.** The contact recipient and the form-signing secret live in
  `contact-config.php`, one directory *above* the web root, which is never served or committed.
- **Contact form defences:** a honeypot field; a signed, time-stamped token (forms submitted within 3
  seconds of loading, older than 2 hours, or not issued by this site are refused); 5 messages per hour
  per visitor; strict validation; and no visitor input in mail headers except a validated `Reply-To`.
- **The recipient address never appears on any page.** Visitors' IP addresses are stored only as
  hashes, for the rate limit.
- **Status messages travel as fixed codes** (`?contact=sent`), so nobody can craft a link that makes
  the site display arbitrary text.
- **Headers:**
  - a Content-Security-Policy that allows only same-origin scripts, styles and fonts (no inline code)
  - HSTS, `X-Frame-Options: DENY`, `nosniff` and a strict referrer policy
- **Fonts and images are self-hosted**, so pages make no third-party requests from the browser. The
  GitHub status check runs on the server.
- **Internal files are never served:** `app/`, `tests/`, the repository metadata and this README all
  return 404.

## Run it locally

```bash
php -S localhost:8000 router.php      # router.php mirrors the .htaccess rules
php tests/run.php                     # 75 checks: form logic, data integrity, every page renders and is well-formed
```

No PHP installed? Use Docker:
`docker run --rm -p 8000:8000 -v "$PWD:/site" -w /site php:8.3-cli php -S 0.0.0.0:8000 router.php`

To see the contact form locally, put a `contact-config.php` one directory above the project (copy
[contact-config.example.php](contact-config.example.php)).

## Deploy (20i)

1. **SSL first.** In My20i, go to *Manage Hosting → israelrocklyn.info → SSL/TLS* and check that the
   free certificate is active. The site redirects every request to HTTPS.
2. **PHP version.** Under *Web Tools → Change PHP Version*, choose PHP 8.3 or later.
3. **Connect the repository.** Under *Web Files → Git™ Version Control*, connect
   `codeROCK-IZZY/Israelrocklyn`, branch `main`, and deploy it to `public_html`. Each later update is
   one click on *Deploy* (or turn on automatic deployment).
4. **Configure the contact form once.** In *File Manager*, create `contact-config.php` in the home
   directory, next to `public_html`, not inside it:

   ```php
   <?php
   return [
       'recipient' => 'your inbox address',
       'secret'    => 'a long random string',    // e.g. from: php -r "echo bin2hex(random_bytes(32));"
       'from'      => 'no-reply@israelrocklyn.info',
   ];
   ```

   Until this file exists, the form is replaced by a short notice, and nothing breaks.
5. **Email deliverability.** Run *Email → DMARC Wizard* and enable *Domainkeys (DKIM)*, so messages sent
   from `no-reply@israelrocklyn.info` reach the inbox instead of spam.

## Editing content

Change the text in `app/data/profile.php` or `app/data/projects.php`. A new project needs:
- an entry in `projects.php`
- optionally, a 1280×800 WebP screenshot in `assets/img/`

The home page, its case-study page and the sitemap pick it up automatically. After changing CSS or
JavaScript, bump `ASSET_VERSION` in `app/bootstrap.php` so browsers fetch the new files.

Fonts: [Space Grotesk](https://github.com/floriankarsten/space-grotesk), [Inter](https://github.com/rsms/inter) and [JetBrains Mono](https://github.com/JetBrains/JetBrainsMono),
under the SIL Open Font License ([licences](assets/fonts)).
