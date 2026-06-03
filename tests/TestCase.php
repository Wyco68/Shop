<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if (! config('app.key')) {
            // Ephemeral PHPUnit-only key (never committed; not for production).
            config([
                'app.key' => 'base64:'.base64_encode(
                    hash('sha256', 'ecommerce-template-phpunit', true)
                ),
            ]);
        }

        return $app;
    }
}
