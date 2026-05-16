<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeploymentCheck extends Command
{
    protected $signature = 'deploy:check
        {--production : Apply production-level validation}
        {--require-config-cache : Fail when Laravel config is not cached}
        {--skip-db : Skip database connectivity and queue table checks}
        {--env-file= : Validate a specific env file instead of the project .env}';

    protected $description = 'Validate environment, config cache, mail, storage, database, and queue setup before deployment.';

    public function handle()
    {
        $production = (bool) $this->option('production') || app()->environment('production');
        $errors = [];
        $warnings = [];
        $ok = [];

        $envValues = $this->loadEnvFile($errors, $warnings);

        $this->checkApplicationConfig($errors, $warnings, $ok, $production);
        $this->checkEnvKeys($envValues, $errors, $warnings, $production);
        $this->checkConfigCache($errors, $warnings, $ok, $production);
        $this->checkMail($errors, $warnings, $ok, $production);
        $this->checkStorage($errors, $ok);
        $this->checkDatabaseAndQueue($errors, $warnings, $ok);

        $this->line('');
        $this->info('Deployment Check');

        foreach ($ok as $message) {
            $this->line('<info>OK</info> ' . $message);
        }

        foreach ($warnings as $message) {
            $this->line('<comment>WARN</comment> ' . $message);
        }

        foreach ($errors as $message) {
            $this->line('<error>FAIL</error> ' . $message);
        }

        if ($errors) {
            $this->line('');
            $this->error('Deployment check failed. Fix the FAIL items before release.');

            return 1;
        }

        $this->line('');
        $this->info('Deployment check passed.');

        return 0;
    }

    private function loadEnvFile(array &$errors, array &$warnings): array
    {
        $path = $this->resolveEnvPath();

        if (!is_file($path)) {
            $errors[] = '.env file is missing.';

            return [];
        }

        if (!is_readable($path)) {
            $errors[] = '.env file is not readable.';

            return [];
        }

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }

        if (!$values) {
            $warnings[] = '.env file was found, but no key/value pairs were parsed.';
        }

        return $values;
    }

    private function resolveEnvPath(): string
    {
        $path = (string) ($this->option('env-file') ?: base_path('.env'));

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || strpos($path, '/') === 0 || strpos($path, '\\\\') === 0) {
            return $path;
        }

        return base_path($path);
    }

    private function checkApplicationConfig(array &$errors, array &$warnings, array &$ok, bool $production): void
    {
        if (!config('app.key')) {
            $errors[] = 'APP_KEY is missing. Run php artisan key:generate before deployment.';
        } else {
            $ok[] = 'APP_KEY is configured.';
        }

        if (!filter_var(config('app.url'), FILTER_VALIDATE_URL)) {
            $errors[] = 'APP_URL must be a valid absolute URL.';
        } else {
            $ok[] = 'APP_URL is valid.';
        }

        if ($production && config('app.debug')) {
            $errors[] = 'APP_DEBUG must be false in production.';
        }

        if ($production && !in_array(config('logging.default'), ['stack', 'daily', 'single'], true)) {
            $warnings[] = 'LOG_CHANNEL should normally be stack, daily, or single in production.';
        }
    }

    private function checkEnvKeys(array $envValues, array &$errors, array &$warnings, bool $production): void
    {
        $required = ['APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_URL', 'DB_CONNECTION', 'DB_DATABASE', 'DB_USERNAME', 'QUEUE_CONNECTION'];

        if ($production) {
            $required = array_merge($required, [
                'MAIL_MAILER',
                'MAIL_HOST',
                'MAIL_PORT',
                'MAIL_USERNAME',
                'MAIL_PASSWORD',
                'MAIL_FROM_ADDRESS',
                'FLEETX_BASIC_AUTH',
                'FLEETX_API_USERNAME',
                'FLEETX_API_PASSWORD',
                'TRAVIS_SYSTEM_EMAIL',
                'TRAVIS_SYSTEM_PASSWORD',
            ]);
        }

        foreach ($required as $key) {
            if (!array_key_exists($key, $envValues) || $this->emptyValue($envValues[$key])) {
                $errors[] = $key . ' is missing or empty in .env.';
            }
        }

        foreach (['DB_PASSWORD', 'TRAVIS_CA_BUNDLE', 'LOG_ADMIN_EMAILS', 'MYSQLDUMP_BINARY'] as $key) {
            if (!array_key_exists($key, $envValues)) {
                $warnings[] = $key . ' is not present in .env. Add it explicitly if the environment depends on it.';
            }
        }
    }

    private function checkConfigCache(array &$errors, array &$warnings, array &$ok, bool $production): void
    {
        $cached = app()->configurationIsCached();

        if ($cached) {
            $ok[] = 'Laravel config cache exists.';
        } elseif ($production || (bool) $this->option('require-config-cache')) {
            $errors[] = 'Laravel config is not cached. Run php artisan config:cache during deployment.';
        } else {
            $warnings[] = 'Laravel config is not cached. This is acceptable locally, but production should cache config.';
        }

        $cachePath = app()->getCachedConfigPath();
        $envPath = base_path('.env');
        if (!$this->option('env-file') && $cached && is_file($envPath) && is_file($cachePath) && filemtime($envPath) > filemtime($cachePath)) {
            $errors[] = '.env is newer than bootstrap/cache/config.php. Run php artisan config:clear && php artisan config:cache.';
        }
    }

    private function checkMail(array &$errors, array &$warnings, array &$ok, bool $production): void
    {
        $mailer = config('mail.default');
        $from = config('mail.from.address');

        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'MAIL_FROM_ADDRESS must be a valid email address.';
        } else {
            $ok[] = 'Mail from address is valid.';
        }

        if ($production && in_array($mailer, ['array', 'log'], true)) {
            $errors[] = 'MAIL_MAILER must not be array or log in production.';
        }

        if ($mailer === 'smtp') {
            foreach (['host', 'port'] as $key) {
                if ($this->emptyValue(config('mail.mailers.smtp.' . $key))) {
                    $errors[] = 'SMTP mail setting mail.mailers.smtp.' . $key . ' is missing.';
                }
            }

            if ($production) {
                foreach (['username', 'password'] as $key) {
                    if ($this->emptyValue(config('mail.mailers.smtp.' . $key))) {
                        $errors[] = 'SMTP mail setting mail.mailers.smtp.' . $key . ' is missing.';
                    }
                }
            }

            $ok[] = 'SMTP mailer configuration was checked.';
        } elseif (!$mailer) {
            $errors[] = 'MAIL_MAILER is missing.';
        } else {
            $warnings[] = 'MAIL_MAILER is set to ' . $mailer . '. Confirm this is intentional for deployment.';
        }
    }

    private function checkStorage(array &$errors, array &$ok): void
    {
        foreach ([storage_path(), base_path('bootstrap/cache')] as $path) {
            if (!is_dir($path) || !is_writable($path)) {
                $errors[] = $path . ' must exist and be writable.';
            } else {
                $ok[] = $path . ' is writable.';
            }
        }
    }

    private function checkDatabaseAndQueue(array &$errors, array &$warnings, array &$ok): void
    {
        if ((bool) $this->option('skip-db')) {
            $warnings[] = 'Database checks were skipped.';

            return;
        }

        try {
            DB::connection()->getPdo();
            $ok[] = 'Database connection succeeded.';
        } catch (\Throwable $exception) {
            $errors[] = 'Database connection failed: ' . $exception->getMessage();

            return;
        }

        if (config('queue.default') === 'sync') {
            $warnings[] = 'QUEUE_CONNECTION is sync. Use database, redis, or sqs for background integrations in production.';
        }

        if (config('queue.default') === 'database') {
            try {
                if (Schema::hasTable('jobs')) {
                    $ok[] = 'Database queue jobs table exists.';
                } else {
                    $errors[] = 'QUEUE_CONNECTION=database but jobs table is missing. Run php artisan migrate.';
                }
            } catch (\Throwable $exception) {
                $errors[] = 'Unable to verify jobs table: ' . $exception->getMessage();
            }
        }
    }

    private function emptyValue($value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return $normalized === '' || $normalized === 'null';
    }
}
