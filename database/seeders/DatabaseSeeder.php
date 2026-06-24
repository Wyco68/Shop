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
     * After migrate, complete first-run setup via CLI:
     *   php artisan store:setup
     */
    public function run(): void
    {
        //
    }
}
