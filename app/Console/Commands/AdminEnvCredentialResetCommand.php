<?php

namespace App\Console\Commands;

use App\Services\AdminBootstrapService;
use App\Services\AdminPasswordService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminEnvCredentialResetCommand extends Command
{
    protected $signature = 'admin:env-password-reset';

    protected $description = 'One-time admin credential reset when ADMIN_RESET_PASSWORD=true';

    public function handle(AdminPasswordService $passwords): int
    {
        if (! filter_var(env('ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->components->error('ADMIN_RESET_PASSWORD is not enabled.');

            return self::FAILURE;
        }

        $targetEmail = env('ADMIN_RESET_EMAIL');
        $plainCredential = env('ADMIN_RESET_PASSWORD_NEW');

        if (! $targetEmail || ! $plainCredential) {
            $this->components->error('Set ADMIN_RESET_EMAIL and ADMIN_RESET_PASSWORD_NEW in the environment.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['credential' => $plainCredential, 'credential_confirmation' => $plainCredential],
            ['credential' => AdminBootstrapService::passwordRules()],
        );

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first('credential'));

            return self::FAILURE;
        }

        try {
            $admin = $passwords->changePassword($targetEmail, $plainCredential, 'env_reset');
        } catch (ValidationException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Credential updated for {$admin->email}. Set ADMIN_RESET_PASSWORD=false immediately.");

        return self::SUCCESS;
    }
}
