<?php

/**
 * Router for `php -S` on Render (and local). Trust TLS termination before Laravel boots.
 */
if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
}

$publicPath = __DIR__;
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');

// Serve built Vite assets and other static files (avoid HTML 404 → JS MIME type errors)
if ($uri !== '/' && $uri !== '' && is_file($publicPath.$uri)) {
    return false;
}

require __DIR__.'/../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
