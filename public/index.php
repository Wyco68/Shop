<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Render (and other TLS terminators) forward HTTPS; PHP's built-in server does not set $_SERVER['HTTPS'] without this.
if (($forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '443';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
