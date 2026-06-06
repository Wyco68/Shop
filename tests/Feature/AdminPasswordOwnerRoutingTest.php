<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\AdminBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPasswordOwnerRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_admin_password_reset_mail_is_routed_to_owner_inbox(): void
    {
        config(['admin.owner_email' => 'herik.dev06@gmail.com']);

        $admin = User::factory()->admin()->create(['email' => 'carpart@admin.test']);

        $route = $admin->routeNotificationForMail(new ResetPasswordNotification('token'));

        $this->assertSame('herik.dev06@gmail.com', $route);
        $this->assertNotSame($admin->email, $route);
    }

    public function test_owner_own_password_reset_mail_still_goes_to_owner_inbox(): void
    {
        config(['admin.owner_email' => 'herik.dev06@gmail.com']);

        User::factory()->admin()->create();
        $owner = app(AdminBootstrapService::class)->createOwner([
            'email' => 'herik.dev06@gmail.com',
            'password' => 'OwnerPassword1!Secure',
        ]);

        $this->assertSame(
            'herik.dev06@gmail.com',
            $owner->routeNotificationForMail(new ResetPasswordNotification('token'))
        );
    }

    public function test_regular_customer_mail_is_unaffected(): void
    {
        config(['admin.owner_email' => 'herik.dev06@gmail.com']);

        $customer = User::factory()->create(['email' => 'customer@store.test']);

        $this->assertSame(
            'customer@store.test',
            $customer->routeNotificationForMail(new ResetPasswordNotification('token'))
        );
    }
}
