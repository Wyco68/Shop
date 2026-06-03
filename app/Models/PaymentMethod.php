<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use App\Services\SecureUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'instructions',
        'config',
        'qr_image_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function qrImageUrl(): ?string
    {
        if (! $this->qr_image_path) {
            return null;
        }

        return app(SecureUploadService::class)->url($this->qr_image_path);
    }

    public function displayInstructions(): string
    {
        $lines = array_filter([
            $this->instructions,
            $this->configLine('bank_name', 'Bank'),
            $this->configLine('account_name', 'Account name'),
            $this->configLine('account_number', 'Account number'),
            $this->configLine('mobile_provider', 'Provider'),
            $this->configLine('mobile_number', 'Number'),
            $this->configLine('wallet_address', 'Wallet'),
            $this->configLine('network', 'Network'),
        ]);

        return implode("\n", $lines);
    }

    private function configLine(string $key, string $label): ?string
    {
        $value = $this->config[$key] ?? null;

        return $value ? "{$label}: {$value}" : null;
    }
}
