# Deployment Checklist

Use this checklist before deploying Logisticaa to staging or production.

## Server Requirements

- [ ] PHP version matches Composer requirements and required extensions are enabled.
- [ ] Web server document root points to `public/`.
- [ ] `storage/` and `bootstrap/cache/` are writable by the web user.
- [ ] Queue worker is configured for `php artisan queue:work`.
- [ ] Scheduler is configured to run `php artisan schedule:run` every minute.
- [ ] `mysqldump` is available, or `MYSQLDUMP_BINARY` points to the full executable path.

## Environment

- [ ] Copy `.env.example` to `.env` for new installs.
- [ ] Set `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG=false`, and `APP_URL`.
- [ ] Set database values: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- [ ] Set `QUEUE_CONNECTION=database` and run migrations so the `jobs` table exists.
- [ ] Set mail values: `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- [ ] Set integration values: `FLEETX_BASIC_AUTH`, `FLEETX_API_USERNAME`, `FLEETX_API_PASSWORD`, `TRAVIS_SYSTEM_EMAIL`, `TRAVIS_SYSTEM_PASSWORD`.
- [ ] Set log admin emails in `LOG_ADMIN_EMAILS`.
- [ ] Set backup options when needed: `BACKUP_PATH`, `MYSQLDUMP_BINARY`, `BACKUP_TIMEOUT`, `BACKUP_GZIP`.

## Deploy Steps

```bash
composer install --no-dev --optimize-autoloader
php artisan down
php artisan backup:database
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan deploy:check --production
php artisan queue:restart
php artisan up
```

## Mail Verification

```bash
php artisan mail:test ops@example.com
```

Confirm the message reaches the inbox and that SPF/DKIM/DMARC are valid for the sending domain.

## Backup Verification

```bash
php artisan backup:database
```

Confirm the backup file exists in `storage/app/backups` or the configured `BACKUP_PATH`. Periodically test restore on a non-production database.

## Post-Deploy Smoke Test

- [ ] Login works.
- [ ] Settings page loads and saves.
- [ ] Integrations page shows FleetX, WheelsEye, and Travis status.
- [ ] FleetX refresh succeeds.
- [ ] LR tracking refresh queues a job.
- [ ] EPOD upload works with a small test image.
- [ ] System logs page loads, export works, and clear actions remain admin-only.
- [ ] Queue worker is processing jobs.
- [ ] `storage/logs/laravel.log` has no new deployment errors.
