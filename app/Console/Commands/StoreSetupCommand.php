<?php

namespace App\Console\Commands;

use App\Enums\CurrencyPosition;
use App\Services\AdminBootstrapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreSetupCommand extends Command
{
    protected $signature = 'store:setup';

    protected $description = 'One-time interactive setup: configure the store and create the first administrator';

    public function handle(AdminBootstrapService $adminBootstrap): int
    {
        if ($adminBootstrap->adminExists()) {
            $this->components->error('An administrator account already exists. Setup has already run.');

            return self::FAILURE;
        }

        $data = [
            'store_name' => $this->ask('Store name'),
            'currency_code' => strtoupper((string) $this->ask('Currency code (3-letter, e.g. USD)')),
            'currency_symbol' => $this->ask('Currency symbol (e.g. $)'),
            'currency_position' => $this->choice('Currency symbol position', CurrencyPosition::values(), 0),
            'name' => $this->ask('Administrator name (optional, defaults to part before @ in email)') ?: null,
            'email' => strtolower((string) $this->ask('Administrator email')),
            'password' => $this->secret('Administrator password'),
            'password_confirmation' => $this->secret('Confirm administrator password'),
        ];

        $validator = Validator::make($data, [
            'store_name' => ['required', 'string', 'max:255'],
            'currency_code' => ['required', 'string', 'size:3', 'alpha:ascii'],
            'currency_symbol' => ['required', 'string', 'min:1', 'max:8'],
            'currency_position' => ['required', Rule::in(CurrencyPosition::values())],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => AdminBootstrapService::passwordRules(),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();

        try {
            $admin = $adminBootstrap->createAdmin($validated);
        } catch (ValidationException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Store '{$validated['store_name']}' configured. Administrator created: {$admin->email}");

        return self::SUCCESS;
    }
}
