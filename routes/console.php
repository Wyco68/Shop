<?php

use App\Services\AdminBootstrapService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:init-admin', function (AdminBootstrapService $bootstrap) {
    if ($bootstrap->adminExists()) {
        error('An administrator already exists. This command can only run once.');

        return self::FAILURE;
    }

    $email = text(
        label: 'Administrator email',
        required: true,
        validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL)
            ? null
            : 'Enter a valid email address.',
    );

    $name = text(
        label: 'Display name (optional)',
        default: strstr($email, '@', true) ?: 'Administrator',
    );

    $plainPassword = password(
        label: 'Password',
        required: true,
        validate: fn (string $value) => strlen($value) >= 12
            ? null
            : 'Password must be at least 12 characters.',
    );

    $confirm = password(
        label: 'Confirm password',
        required: true,
    );

    if ($plainPassword !== $confirm) {
        error('Passwords do not match.');

        return self::FAILURE;
    }

    $validator = Validator::make(
        ['password' => $plainPassword, 'password_confirmation' => $confirm],
        ['password' => AdminBootstrapService::passwordRules()],
    );

    if ($validator->fails()) {
        error($validator->errors()->first('password'));

        return self::FAILURE;
    }

    $user = $bootstrap->createAdmin([
        'name' => $name,
        'email' => $email,
        'password' => $plainPassword,
    ]);

    info("Administrator created: {$user->email}");

    return self::SUCCESS;
})->purpose('Create the first administrator account (one-time only)');
