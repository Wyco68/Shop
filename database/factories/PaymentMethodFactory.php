<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        $code = fake()->unique()->slug(2);

        return [
            'name' => fake()->words(3, true),
            'code' => $code,
            'type' => PaymentMethodType::Bank,
            'config' => [
                'bank_name' => 'Test Bank',
                'account_number' => '1234567890',
            ],
            'instructions' => fake()->paragraph(),
            'qr_image_path' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
