<?php

namespace App\Console\Commands;

use App\Enums\CurrencyPosition;
use App\Services\StoreSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CurrencyForceChangeCommand extends Command
{
    protected $signature = 'currency:force-change
                            {--code= : ISO 4217 currency code}
                            {--symbol= : Currency symbol}
                            {--position= : before or after}
                            {--force : Skip interactive confirmation}';

    protected $description = 'One-time CLI override to change locked store currency (logs action)';

    public function handle(StoreSettingsService $settings): int
    {
        $setting = $settings->get();

        $this->components->warn('Changing base currency after go-live can break order history, payments, and analytics.');
        $this->line("Current: {$setting->currency_code} ({$setting->currency_symbol}), position: {$setting->currency_position}");

        if (! $this->option('force') && ! confirm('Do you understand the risks and want to continue?', false)) {
            $this->components->info('Aborted.');

            return self::SUCCESS;
        }

        $code = $this->option('code') ?? text('Currency code (3 letters)', required: true);
        $symbol = $this->option('symbol') ?? text('Currency symbol', required: true);
        $position = $this->option('position') ?? select(
            label: 'Symbol position',
            options: [
                CurrencyPosition::Before->value => 'Before amount',
                CurrencyPosition::After->value => 'After amount',
            ],
            default: $setting->currency_position ?? CurrencyPosition::Before->value,
        );

        $validator = Validator::make([
            'currency_code' => strtoupper($code),
            'currency_symbol' => $symbol,
            'currency_position' => $position,
        ], [
            'currency_code' => ['required', 'string', 'size:3', 'alpha:ascii'],
            'currency_symbol' => ['required', 'string', 'min:1', 'max:8'],
            'currency_position' => ['required', Rule::in(CurrencyPosition::values())],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();

        $updated = $settings->forceChangeCurrency(
            $validated['currency_code'],
            $validated['currency_symbol'],
            CurrencyPosition::from($validated['currency_position']),
        );

        Log::warning('Store currency force-changed via CLI', [
            'currency_code' => $updated->currency_code,
            'currency_symbol' => $updated->currency_symbol,
            'currency_position' => $updated->currency_position,
            'currency_locked' => $updated->currency_locked,
        ]);

        $this->components->info("Currency updated to {$updated->currency_code} ({$updated->currency_symbol}). Currency remains locked.");

        return self::SUCCESS;
    }
}
