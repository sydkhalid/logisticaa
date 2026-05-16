# GitHub to Hostinger CI/CD

This project deploys to Hostinger through GitHub Actions over SSH.

## 1. Enable SSH on Hostinger

In Hostinger hPanel, enable SSH for the hosting account and note:

- SSH host
- SSH username
- SSH port, commonly `65002`
- Project path, for example `/home/u123456789/domains/example.com/public_html`

For Laravel, the web root should point to the `public` directory when possible. If Hostinger points the domain directly at `public_html`, keep this repository contents in that deploy path only when `public/index.php` is the file being served.

## 2. Create an SSH key for GitHub Actions

Create a deploy key on your local machine:

```bash
ssh-keygen -t ed25519 -C "github-hostinger-logisticaa" -f hostinger_github_deploy
```

Add the public key `hostinger_github_deploy.pub` to Hostinger SSH authorized keys.

## 3. Add GitHub Secrets

In GitHub, open:

`Settings > Secrets and variables > Actions > New repository secret`

Add these secrets:

```text
HOSTINGER_HOST=your-hostinger-ssh-host
HOSTINGER_PORT=65002
HOSTINGER_USER=your-hostinger-ssh-user
HOSTINGER_SSH_KEY=full private key from hostinger_github_deploy
HOSTINGER_DEPLOY_PATH=/home/your-user/domains/your-domain/public_html
HOSTINGER_PHP_BINARY=php
```

If Hostinger requires a specific PHP binary, set `HOSTINGER_PHP_BINARY`, for example:

```text
/usr/bin/php
```

## 4. Prepare the Server Once

The server must already have a valid `.env` file in `HOSTINGER_DEPLOY_PATH`.

Run once on Hostinger SSH:

```bash
cd /home/your-user/domains/your-domain/public_html
php artisan storage:link
php artisan deploy:check --production
```

Also configure:

- Cron: `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
- Queue worker through Hostinger process manager or a cron fallback.

## 5. Deploy

Push to `main` or `master`, or run the workflow manually from:

`GitHub > Actions > Hostinger CI/CD > Run workflow`

The workflow will:

- Install PHP dependencies.
- Run tests.
- Build frontend assets.
- Package the release without `.env`.
- Upload the release to Hostinger.
- Run migrations, clear/cache config, check deployment health, and restart queues.

## Notes

- Do not commit `.env`.
- Keep production secrets only in the server `.env` and GitHub Actions secrets.
- This repo currently uses route closures, so the workflow does not run `php artisan route:cache`.
