<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OperationalCommandsTest extends TestCase
{
    public function test_deploy_check_passes_for_valid_local_env_without_db_check()
    {
        $envPath = $this->writeEnvFile([
            'APP_NAME' => 'Logisticaa',
            'APP_ENV' => 'local',
            'APP_KEY' => 'base64:abcdefghijklmnopqrstuvwxyz0123456789=',
            'APP_URL' => 'http://localhost',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'logisticaa',
            'DB_USERNAME' => 'root',
            'QUEUE_CONNECTION' => 'database',
            'DB_PASSWORD' => '',
            'LOG_ADMIN_EMAILS' => 'admin@example.com',
            'TRAVIS_CA_BUNDLE' => '',
            'MYSQLDUMP_BINARY' => 'mysqldump',
        ]);

        Config::set('app.key', 'base64:abcdefghijklmnopqrstuvwxyz0123456789=');
        Config::set('app.url', 'http://localhost');
        Config::set('mail.default', 'smtp');
        Config::set('mail.from.address', 'no-reply@example.com');
        Config::set('mail.mailers.smtp.host', 'smtp.example.com');
        Config::set('mail.mailers.smtp.port', 587);

        $this->artisan('deploy:check', [
            '--env-file' => $envPath,
            '--skip-db' => true,
        ])->assertExitCode(0);
    }

    public function test_deploy_check_fails_for_invalid_mail_from_address()
    {
        $envPath = $this->writeEnvFile([
            'APP_NAME' => 'Logisticaa',
            'APP_ENV' => 'production',
            'APP_KEY' => 'base64:abcdefghijklmnopqrstuvwxyz0123456789=',
            'APP_URL' => 'https://logisticaa.example.com',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'logisticaa',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => 'secret',
            'QUEUE_CONNECTION' => 'database',
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.example.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => 'smtp-user',
            'MAIL_PASSWORD' => 'smtp-pass',
            'MAIL_FROM_ADDRESS' => 'not-an-email',
            'FLEETX_BASIC_AUTH' => 'Basic token',
            'FLEETX_API_USERNAME' => 'fleetx-user',
            'FLEETX_API_PASSWORD' => 'fleetx-pass',
            'TRAVIS_SYSTEM_EMAIL' => 'connect@example.com',
            'TRAVIS_SYSTEM_PASSWORD' => 'travis-pass',
            'LOG_ADMIN_EMAILS' => 'admin@example.com',
            'TRAVIS_CA_BUNDLE' => '',
            'MYSQLDUMP_BINARY' => 'mysqldump',
        ]);

        Config::set('app.key', 'base64:abcdefghijklmnopqrstuvwxyz0123456789=');
        Config::set('app.url', 'https://logisticaa.example.com');
        Config::set('app.debug', false);
        Config::set('mail.default', 'smtp');
        Config::set('mail.from.address', 'not-an-email');
        Config::set('mail.mailers.smtp.host', 'smtp.example.com');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.mailers.smtp.username', 'smtp-user');
        Config::set('mail.mailers.smtp.password', 'smtp-pass');

        $this->artisan('deploy:check', [
            '--env-file' => $envPath,
            '--production' => true,
            '--skip-db' => true,
        ])->assertExitCode(1);
    }

    public function test_backup_database_rejects_unsupported_connection()
    {
        Config::set('database.connections.sqlite_test_backup', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->artisan('backup:database', [
            '--connection' => 'sqlite_test_backup',
        ])->assertExitCode(1);
    }

    private function writeEnvFile(array $values): string
    {
        $directory = storage_path('framework/testing');
        File::ensureDirectoryExists($directory);
        $path = $directory . DIRECTORY_SEPARATOR . uniqid('deploy-check-', true) . '.env';

        $lines = [];
        foreach ($values as $key => $value) {
            $lines[] = $key . '=' . $value;
        }

        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);

        return $path;
    }
}
