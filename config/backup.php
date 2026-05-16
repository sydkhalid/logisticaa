<?php

return [
    'path' => env('BACKUP_PATH') ?: storage_path('app/backups'),
    'mysqldump_binary' => env('MYSQLDUMP_BINARY', env('DB_DUMP_BINARY', 'mysqldump')),
    'timeout' => (int) env('BACKUP_TIMEOUT', 300),
    'gzip' => filter_var(env('BACKUP_GZIP', true), FILTER_VALIDATE_BOOLEAN),
];
