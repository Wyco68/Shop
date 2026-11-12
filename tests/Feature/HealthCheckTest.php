<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Exceptions;
use Tests\TestCase;

// No RefreshDatabase: /up only runs `select 1`, and its teardown rollback would run against the
// deliberately unreachable default connection set below.
class HealthCheckTest extends TestCase
{
    public function test_up_is_healthy_when_the_database_is_reachable(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertSee('Application up');
    }

    public function test_up_fails_without_leaking_details_when_the_database_is_unreachable(): void
    {
        Exceptions::fake();

        config([
            'app.debug' => false,
            'database.connections.unreachable' => [
                'driver' => 'sqlite',
                'database' => '/nonexistent-health-check-dir/database.sqlite',
                'prefix' => '',
            ],
            'database.default' => 'unreachable',
        ]);

        $this->get('/up')
            ->assertStatus(500)
            ->assertSee('Application experiencing problems')
            ->assertDontSee('nonexistent-health-check-dir');

        // The real cause still reaches the logs.
        Exceptions::assertReportedCount(1);
    }
}
