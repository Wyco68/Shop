<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Password1!Secure';

    public function test_registration_screen_can_be_rendered(): void
    {
        User::factory()->admin()->create();

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_and_receive_verification_email(): void
    {
        Notification::fake();

        User::factory()->admin()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'phone_num' => '555-0001',
            'address' => '123 Test St',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register.success'));
        $response->assertSessionHas('email', 'test@example.com');

        $user = User::query()->where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_registration_requires_strong_password(): void
    {
        User::factory()->admin()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'phone_num' => '555-0001',
            'address' => '123 Test St',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
