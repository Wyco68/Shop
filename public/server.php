<?php

/**
 * Router for `php -S` on Render (and local). Trust TLS termination before Laravel boots.
 */
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
}

require __DIR__.'/../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
