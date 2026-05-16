<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
define('LOGISTICAA_V2_FRONT_CONTROLLER', true);

$_ENV['V2_ROUTE_PREFIX'] = '';
$_SERVER['V2_ROUTE_PREFIX'] = '';
putenv('V2_ROUTE_PREFIX=');

if (file_exists(__DIR__ . '/../storage/framework/maintenance.php')) {
    require __DIR__ . '/../storage/framework/maintenance.php';
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = tap($kernel->handle(
    $request = Request::capture()
))->send();

$kernel->terminate($request, $response);
