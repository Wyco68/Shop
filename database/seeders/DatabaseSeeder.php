<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Intentionally empty — no demo catalog or users.
     *
     * After migrate, create the first admin:
     *   php artisan app:init-admin
     */
    public function run(): void
    {
        //
    }
}
