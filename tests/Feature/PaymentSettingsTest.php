<?php

namespace Tests\Feature;

use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_payment_method_with_encrypted_config(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.settings.payments.store'), [
            'name' => 'Kpay',
            'type' => PaymentMethodType::Mobile->value,
            'instructions' => 'Transfer to this KPay account.',
            'config' => [
                'mobile_provider' => 'KPay',
                'mobile_number' => '09123456789',
            ],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.settings.payments.index'));
        $response->assertSessionHas('success');

        $method = PaymentMethod::query()->where('code', 'kpay')->first();
        $this->assertNotNull($method);
        $this->assertSame(PaymentMethodType::Mobile, $method->type);
        $this->assertSame('KPay', $method->config['mobile_provider']);
        $this->assertSame('09123456789', $method->config['mobile_number']);
    }
}
