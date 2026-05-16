<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--connection= : Database connection to back up}
        {--path= : Directory where the backup file should be written}
        {--filename= : Backup filename}
        {--plain : Write plain .sql instead of compressed .sql.gz}';

    protected $description = 'Create a MySQL database backup using mysqldump.';

    public function handle()
    {
        $connectionName = $this->option('connection') ?: config('database.default');
        $connection = config('database.connections.' . $connectionName);

        if (!$connection) {
            $this->error('Database connection not found: ' . $connectionName);

            return 1;
        }

        if (($connection['driver'] ?? null) !== 'mysql') {
            $this->error('backup:database currently supports mysql connections only.');

            return 1;
        }

        $database = $connection['database'] ?? null;
        if (!$database) {
            $this->error('Database name is missing for connection: ' . $connectionName);

            return 1;
        }

        $compress = !((bool) $this->option('plain')) && (bool) config('backup.gzip', true);
        $directory = $this->option('path') ?: config('backup.path');
        File::ensureDirectoryExists($directory);

        $filename = $this->option('filename') ?: $this->defaultFilename($database, $compress);
        $outputPath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $handle = $compress ? gzopen($outputPath, 'wb9') : fopen($outputPath, 'wb');
        if (!$handle) {
            $this->error('Unable to open backup file for writing: ' . $outputPath);

            return 1;
        }

        $command = $this->buildDumpCommand($connection, $database);
        $env = [];
        if (!empty($connection['password'])) {
            $env['MYSQL_PWD'] = (string) $connection['password'];
        }

        $this->info('Writing database backup to ' . $outputPath);

        $errors = '';
        $process = new Process($command, base_path(), $env, null, (int) config('backup.timeout', 300));
        $process->run(function ($type, $buffer) use ($handle, $compress, &$errors) {
            if ($type === Process::ERR) {
                $errors .= $buffer;

                return;
            }

            if ($compress) {
                gzwrite($handle, $buffer);
            } else {
                fwrite($handle, $buffer);
            }
        });

        $compress ? gzclose($handle) : fclose($handle);

        if (!$process->isSuccessful()) {
            @unlink($outputPath);
            $this->error('Database backup failed.');
            if ($errors !== '') {
                $this->line(trim($errors));
            }

            return 1;
        }

        $this->info('Database backup completed: ' . $outputPath);

        return 0;
    }

    private function buildDumpCommand(array $connection, string $database): array
    {
        $command = [
            config('backup.mysqldump_binary') ?: 'mysqldump',
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--host=' . ($connection['host'] ?? '127.0.0.1'),
            '--port=' . ($connection['port'] ?? '3306'),
            '--user=' . ($connection['username'] ?? ''),
        ];

        if (!empty($connection['unix_socket'])) {
            $command[] = '--socket=' . $connection['unix_socket'];
        }

        $command[] = $database;

        return $command;
    }

    private function defaultFilename(string $database, bool $compress): string
    {
        $safeDatabase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $database) ?: 'database';

        return $safeDatabase . '-' . now()->format('Ymd-His') . '.sql' . ($compress ? '.gz' : '');
    }
}
