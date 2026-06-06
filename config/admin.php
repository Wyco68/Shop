<?php

return [

    'password' => [
        'web_change_enabled' => env('ADMIN_PASSWORD_WEB_CHANGE_ENABLED', true),
        'reset_enabled' => env('ADMIN_PASSWORD_RESET_ENABLED', true),
        'change_cooldown_minutes' => (int) env('ADMIN_PASSWORD_CHANGE_COOLDOWN_MINUTES', 60),
        'max_attempts' => (int) env('ADMIN_PASSWORD_MAX_ATTEMPTS', 3),
        'rate_limit_minutes' => (int) env('ADMIN_PASSWORD_RATE_LIMIT_MINUTES', 15),
        'check_breached' => env('ADMIN_PASSWORD_CHECK_BREACHED', true),
    ],

    // Real owner inbox. All mail for the shared/demo admin account (password
    // resets included) is routed here instead of whatever email is on file
    // for that account — see User::routeNotificationForMail().
    'owner_email' => env('ADMIN_OWNER_EMAIL', 'herik.dev06@gmail.com'),

    'demo' => [
        // Shared/demo admin sessions (any non-owner admin) are capped at this
        // many minutes and every write they make is undone on logout/expiry.
        'enabled' => env('ADMIN_DEMO_MODE_ENABLED', true),
        'session_minutes' => (int) env('ADMIN_DEMO_SESSION_MINUTES', 30),
    ],

];
