<?php

use App\Services\AdminBootstrapService;
use App\Services\AdminPasswordService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:change-password', function (AdminPasswordService $passwords) {
    $email = text(
        label: 'Admin email',
        required: true,
        validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL)
            ? null
            : 'Enter a valid email address.',
    );

    $plainPassword = password(
        label: 'New password',
        required: true,
    );

    $confirm = password(
        label: 'Confirm new password',
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

    try {
        $admin = $passwords->changePassword($email, $plainPassword, 'cli');
    } catch (\Illuminate\Validation\ValidationException $e) {
        error($e->getMessage());

        return self::FAILURE;
    }

    info("Password updated for {$admin->email}.");

    return self::SUCCESS;
})->purpose('Change an administrator password (CLI only)');

Schedule::command('notifications:prune-read')->daily();
