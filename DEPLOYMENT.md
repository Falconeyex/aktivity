# AKTIVITY deployment (Active24 Smart Hosting)

Upload this project to the Active24 web root folder `/microview.cz/aktivity`, which is served as `https://www.microview.cz/aktivity`.

## 1. PHP

In the Active24 control panel, set the site PHP version to **8.1 or newer**.

Required extensions (usually enabled by default):

- `pdo_mysql`
- `openssl`
- `mbstring`
- `curl`
- `json`
- `session`

Confirm Argon2id is available (`PASSWORD_ARGON2ID`). PHP 8.1+ includes it.

## 2. Files

Upload the project over FTP/SFTP. Do **not** upload `config.example.php` as the live config.

Suggested remote layout:

```
/microview.cz/aktivity/
  api/
  assets/
  cache/
  core/
  sql/
  vendor/          (after Composer, or upload from a machine that ran composer install)
  .htaccess
  config.php
  index.php
  reset_pw.php
  seed.php
  composer.json
```

Deny rules in `.htaccess` block web access to `config.php`, `core/`, `sql/`, `cache/`, and `vendor/`. PHP can still include those files from disk.

## 3. config.php

On the server:

1. Copy `config.example.php` to `config.php`.
2. Set real values:

| Key | Value |
| --- | --- |
| `app_base` | `/aktivity` |
| `app_url` | `https://www.microview.cz/aktivity` |
| `session_secure` | `true` (HTTPS required) |
| `db.*` | MariaDB host, database name, user, password from Active24 |
| `smtp.*` | SMTP for `postmaster@microview.cz` |
| `openai.api_key` | ChatGPT API key |
| `openai.model` | e.g. `gpt-4o-mini` |

Never commit `config.php`.

For local HTTP testing only, set `app_base` to `''` and `session_secure` to `false`.

## 4. Composer libraries (production)

This step is for **the live Active24 site**, not a local-only test.

Active24 Smart Hosting typically has no Composer command. Run Composer **on your PC**, then **upload the `vendor/` folder** together with the rest of the app.

```bash
composer install --no-dev --optimize-autoloader
```

That creates `vendor/` with:

- `phpmailer/phpmailer` — reliable SMTP for password-reset mail
- `ezyang/htmlpurifier` — sanitizes card HTML before it is stored

Without `vendor/` the site still starts: mail uses PHP `mail()`, and HTML is cleaned with a built-in allowlist. Use Composer for production so reset emails and sanitization are the real libraries.

Also create `cache/htmlpurifier/` on the server and make it writable by PHP (`0750` or `0755`). `.htaccess` inside `cache/` denies web access.

You can run the same `composer install` locally if you want those libraries while developing. That is optional; production still needs `vendor/` uploaded.

## 5. Database

1. Create a MariaDB database and user in Active24.
2. Import [sql/schema.sql](sql/schema.sql) only. Do not run any legacy schema.
3. Open `https://www.microview.cz/aktivity/seed.php` once. It creates:

   - user `falconeyex@gmail.com`
   - password hashed with Argon2id (`Ferdicek2026*`)
   - 20 dummy cards
   - default settings (English, light theme, AI sidebar pinned)

4. If any user already exists, seed.php does nothing.
5. Delete `seed.php` from the server after a successful seed.

## 6. Permissions

| Path | Permission |
| --- | --- |
| directories | `0755` |
| PHP/JS/CSS | `0644` |
| `config.php` | `0640` if the panel allows, otherwise `0644` |
| `cache/htmlpurifier/` | writable by the PHP user |

## 7. HTTPS and sessions

Use the Active24 SSL certificate. Session cookies are `HttpOnly`, `Secure`, and `SameSite=Strict`, with a 2-hour idle timeout. `Secure` cookies are not sent over plain HTTP.

## 8. Smoke test

1. Open `/aktivity/` — sign-in form.
2. Sign in as the test account.
3. Confirm six columns and dummy cards.
4. Create, edit, drag, recycle, restore, and purge a card.
5. Switch theme and Czech/English; reload to confirm persistence.
6. Request a password reset and confirm the 10-minute link to `/aktivity/reset_pw.php`.
7. Pin/unpin the AI sidebar (split on desktop, overlay on tablet/phone).
8. Check phone (~390px), tablet (~768px), and desktop (~1440px).

## 9. Local development

Local testing is separate from the Composer upload above.

```bash
# from the project root, after config.php app_base is ''
php -S localhost:8080
```

Import `sql/schema.sql` into local MariaDB, then open `http://localhost:8080/seed.php`. Optional: `composer install` on this machine if you want PHPMailer and HTMLPurifier while testing.
