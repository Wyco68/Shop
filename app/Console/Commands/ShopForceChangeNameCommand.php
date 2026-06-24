<?php

namespace App\Console\Commands;

use App\Services\StoreSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class ShopForceChangeNameCommand extends Command
{
    protected $signature = 'shop:force-change-name
                            {--name= : New store name}
                            {--force : Skip interactive confirmation}';

    protected $description = 'One-time CLI override to change the store name set via store:setup (logs action)';

    public function handle(StoreSettingsService $settings): int
    {
        $setting = $settings->get();

        $this->line("Current store name: {$setting->store_name}");

        if (! $this->option('force') && ! confirm('Change the store name?', false)) {
            $this->components->info('Aborted.');

            return self::SUCCESS;
        }

        $name = $this->option('name') ?? text('New store name', required: true);

        $validator = Validator::make(
            ['store_name' => $name],
            ['store_name' => ['required', 'string', 'max:255']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $updated = $settings->forceChangeStoreName($validator->validated()['store_name']);

        Log::warning('Store name force-changed via CLI', [
            'store_name' => $updated->store_name,
        ]);

        $this->components->info("Store name updated to \"{$updated->store_name}\".");

        return self::SUCCESS;
    }
}
