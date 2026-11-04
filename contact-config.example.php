<?php
/**
 * Copy this file to contact-config.php ONE DIRECTORY ABOVE the website folder (your hosting home
 * directory, next to public_html), then fill it in. It is never served and never committed.
 *
 *   recipient  where contact-form messages are delivered
 *   secret     a long random string used to sign form tokens; generate one with
 *              php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
 *   from       the sender address; must be on this domain so SPF and DKIM pass
 */

return [
    'recipient' => 'you@example.com',
    'secret' => 'replace-with-64-random-hex-characters',
    'from' => 'no-reply@israelrocklyn.info',
];
