<?php

namespace Tests\Concerns;

use App\Models\User;

trait BootstrapsStore
{
    protected function bootstrapStore(): void
    {
        if (! User::hasAdmin()) {
            User::factory()->admin()->create();
        }
    }
}
