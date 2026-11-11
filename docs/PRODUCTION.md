# Production release runbook — 2026-11-11

Target: one Linux host, Nginx, PHP-FPM 8.3, MySQL 8, persistent database cache and queue.
This is a release candidate, not a certification that an unconfigured host is production-ready.

## Install on a clean host

1. Provision PHP extensions: curl, dom, fileinfo, mbstring, openssl, pdo_mysql, tokenizer, xml, zip and pcntl for workers. Install Composer 2, Node 22 for asset builds, Nginx and Supervisor. Use a maintained OS and PHP security updates.
2. Create a dedicated database and least-privilege application DB user. Keep MySQL private. Use a separate migration credential if your operations policy requires it. Do not use the root DB account.
3. Check out the reviewed release under `/var/www/garagelog/releases/<commit>`; share `.env` and `storage` outside the release directories. `current` points at the active release. Keep the previous release for rollback. Do not copy local `.env`, logs, vendor or demo data to the server.
4. Copy `.env.production.example` into the shared `.env`; fill the real HTTPS origin, DB credentials and sender/provider credentials using the host's secrets manager. Generate APP_KEY ONCE using `php artisan key:generate`. Preserve it securely across deployments and backups.
5. Run in the release directory:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
composer audit --locked --no-dev
npm ci
npm audit
npm run build
php artisan migrate --force
php artisan optimize
```

Give the PHP-FPM user write access only to storage and bootstrap/cache. Protect `.env` with restrictive filesystem permissions. Do not use chmod 777. Do not run `db:seed`, `migrate:fresh`, or `key:generate` on an existing production installation.

6. On the first install, run `php artisan app:create-admin` interactively. Supply a unique password through hidden input; do not put it into shell arguments. Confirm the admin email through the web flow. Demo seeds refuse production.
7. Adapt `deploy/nginx.conf`, install a valid TLS certificate, verify with `nginx -t`, and reload Nginx. The document root MUST end in `/public`. Match the PHP-FPM socket to your installed PHP version. Deny unknown hosts at your ingress/firewall. Only enter real proxy IPs in TRUSTED_PROXIES; never use a blanket wildcard.
8. Install `deploy/supervisor.conf`, create its log path with suitable permissions, and start the worker. Install `deploy/crontab.example` under the app user; configure log rotation for scheduler and worker logs. The definition alone does not start a scheduler.
9. Run `php artisan app:production-check`. All checks must pass. This is read-only and does not print secrets. It intentionally fails on the local development environment and demo database.

## Required acceptance on staging and production

- Check HTTP redirects to HTTPS; `/up` returns 200. Externally monitor `/up` every minute, and alert an operator on consecutive failures. `/up` only proves app boot, not the database or email provider.
- Run production-check from an operator job daily and alert on failure. Independently test the database, queue age, scheduler execution, disk space, PHP exceptions, failed_jobs and SMTP failures.
- Use two test workshops and two owners: confirm isolation, invitation rejection, consent/revocation, service writes, and receipt access boundaries.
- Deliver verification and password-reset mail to a real controlled inbox; test link host/HTTPS/expiry. Confirm SPF/DKIM/DMARC using your mail provider's instructions. Do not enable real-customer reminders until delivery and consent are checked.
- Print a receipt to PDF from the deployed browser. It is a service summary, not a tax invoice or payment receipt.
- Use a synthetic reminder due date and verify exactly the intended recipient receives it; remove synthetic data through a controlled staging reset, not production SQL.
- Run the MySQL CI integration suite; local SQLite success alone is insufficient. Load-test expected workshop concurrency on staging, without sending real mail; establish response-time/error thresholds before taking payment.
- Publish your operator identity, support contact, privacy/retention terms, cancellation terms and pricing. Agree how customer exports/deletion requests and service corrections will be handled. Draft terms are not a substitute for review appropriate to your business.

## Backup and restore before accepting customer data

Use the hosting provider's encrypted automated DB snapshots or a protected MySQL option file (passwords never in command arguments) with `mysqldump --single-transaction --routines --triggers`. Schedule at least daily; consider point-in-time recovery if losing a day's entries is unacceptable. Copy backups off-host with restricted access. Include shared storage and encrypted APP_KEY recovery material, stored separately from public artifacts. Suggested initial retention: 7 daily + 4 weekly copies, adjusted to your stated policy.

Restore a backup to an ISOLATED staging database before launch and monthly thereafter. Disable outgoing email and scheduler on the restored environment, inject its separate DB credentials, run migration status and integrity checks, and verify sample history/parts/audit rows and login. Record restore duration and latest recovered record. A backup without a successful restore drill is not accepted. Do not restore over live production to test this.

## Updating and rollback

Back up before migrations. Prepare a new immutable release, run CI, install dependencies from lock files, and inspect migrations for backward compatibility. During a pilot use a short announced maintenance window (`php artisan down --retry=60`); stop new writes, apply migrations, switch current to the prepared release, optimize, restart workers (`php artisan queue:restart`), run readiness/smoke tests and `php artisan up`. If a step fails keep maintenance enabled and investigate; never automatically bring up a partially applied release.

Rollback application code by switching current to the prior compatible release, optimizing and restarting workers. Do not blindly run migrate:rollback: data/schema rollback requires a tested plan. The service_audits migration is additive, so keep its table when rolling back application code. Record incident times, affected users and recovery actions.

## Implemented controls and limits

Security headers/CSP, per-client API limits, canonical production host validation, finite API token age (including legacy tokens), protected email workflows, disabled production demo seeds, safe interactive admin creation, no permanent service deletion, and service revision snapshots saved in the write transaction.

Audit snapshots cover service creates/updates through the application, including parts. Existing records have no retroactive audit history; this is not a tamper-proof database ledger. Direct DB administrators can still modify data. Phone/legal vehicle ownership verification, automated billing and accounting/tax integrations are outside the pilot. Manual invoicing for your software subscription can support an initial paid pilot. Do not market these missing features.

Sources: https://laravel.com/docs/12.x/deployment and https://laravel.com/docs/12.x/queues (production caching, public document root and process monitoring).
