# israelrocklyn.info

The source of [israelrocklyn.info](https://israelrocklyn.info), the portfolio of Israel Rocklyn, AI & Cloud
Software Engineer.

It is a small PHP site with no framework, no database and no build step: content lives in two PHP data
files, pages are rendered from shared templates, and the only dynamic feature is a contact form. That
keeps it fast, cheap to host and easy to audit.

| | |
|---|---|
| **Pages** | home, a case study per project (`/work/<slug>`), a 404 page and a generated `sitemap.xml` |
| **Content** | [app/data/profile.php](app/data/profile.php) (about, education, skills, services) and [app/data/projects.php](app/data/projects.php) (the projects and their measured results) |
| **Design** | editorial layout in plain CSS: Fraunces and Inter, self-hosted, light and dark palettes that follow the visitor's system setting |
| **JavaScript** | progressive enhancement only (mobile menu, project filters, inline form errors); everything works without it |
| **Hosting** | Apache + PHP 8.2 or later (20i Linux hosting); deployed from this repository with 20i's Git Version Control |

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
- **Fonts and images are self-hosted**, so pages make no third-party requests.
- **Internal files are never served:** `app/`, `tests/`, the repository metadata and this README all
  return 404.

## Run it locally

```bash
php -S localhost:8000 router.php      # router.php mirrors the .htaccess rules
php tests/run.php                     # 58 checks: form logic, and every page renders and is well-formed
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

Fonts: [Fraunces](https://github.com/undercasetype/Fraunces) and [Inter](https://github.com/rsms/inter),
under the SIL Open Font License ([licences](assets/fonts)).
