<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Bank Transfer',
                'code' => 'bank_transfer',
                'instructions' => "Transfer the exact order total to:\n\nBank: Example Bank\nAccount: 1234567890\nName: CarPart Ltd\n\nInclude your order number in the transfer reference.",
                'sort_order' => 1,
            ],
            [
                'name' => 'GCash',
                'code' => 'gcash',
                'instructions' => "Send payment via GCash to:\n\nMobile: 09XX XXX XXXX\nName: CarPart Store\n\nUse your order number as the payment note.",
                'sort_order' => 2,
            ],
            [
                'name' => 'PayMaya',
                'code' => 'paymaya',
                'instructions' => "Send payment via PayMaya to:\n\nMobile: 09XX XXX XXXX\nName: CarPart Store\n\nUse your order number as the payment note.",
                'sort_order' => 3,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                array_merge($method, ['is_active' => true])
            );
        }
    }
}
