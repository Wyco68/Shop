<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\SecureUploadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SecureUploadService $uploads,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PaymentMethod::class);

        $paymentMethods = PaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get();
        $types = PaymentMethodType::cases();

        return view('admin.settings.payments', compact('paymentMethods', 'types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PaymentMethod::class);

        $data = $this->validated($request);

        if ($request->hasFile('qr_image')) {
            try {
                $data['qr_image_path'] = $this->uploads->storeImage(
                    $request->file('qr_image'),
                    'payment-methods',
                    SecureUploadService::categoryIconMimes(),
                    SecureUploadService::MAX_QR_KB,
                );
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        PaymentMethod::create($data);

        return redirect()->route('admin.settings.payments.index')->with('success', 'Payment method created.');
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorize('update', $paymentMethod);

        $data = $this->validated($request, $paymentMethod);

        if ($request->hasFile('qr_image')) {
            try {
                $this->uploads->deleteIfExists($paymentMethod->qr_image_path);
                $data['qr_image_path'] = $this->uploads->storeImage(
                    $request->file('qr_image'),
                    'payment-methods',
                    SecureUploadService::categoryIconMimes(),
                    SecureUploadService::MAX_QR_KB,
                );
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        $paymentMethod->update($data);

        return redirect()->route('admin.settings.payments.index')->with('success', 'Payment method updated.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorize('delete', $paymentMethod);

        if ($paymentMethod->orders()->exists()) {
            return back()->with('error', 'Cannot delete a payment method used by existing orders.');
        }

        $this->uploads->deleteIfExists($paymentMethod->qr_image_path);
        $paymentMethod->delete();

        return redirect()->route('admin.settings.payments.index')->with('success', 'Payment method deleted.');
    }

    private function validated(Request $request, ?PaymentMethod $existing = null): array
    {
        $type = $request->input('type', PaymentMethodType::Bank->value);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('payment_methods', 'code')->ignore($existing?->id)],
            'type' => ['required', Rule::in(PaymentMethodType::values())],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'qr_image' => ['nullable', 'file', 'max:'.SecureUploadService::MAX_QR_KB],
            'config' => ['nullable', 'array'],
            'config.bank_name' => ['nullable', 'string', 'max:255'],
            'config.account_name' => ['nullable', 'string', 'max:255'],
            'config.account_number' => ['nullable', 'string', 'max:64'],
            'config.mobile_provider' => ['nullable', 'string', 'max:100'],
            'config.mobile_number' => ['nullable', 'string', 'max:32'],
            'config.wallet_address' => ['nullable', 'string', 'max:255'],
            'config.network' => ['nullable', 'string', 'max:64'],
        ]);

        $config = array_filter($validated['config'] ?? [], fn ($v) => $v !== null && $v !== '');

        return [
            'name' => $validated['name'],
            'code' => $validated['code'] ?? Str::slug($validated['name'], '_'),
            'type' => $type,
            'instructions' => $validated['instructions'] ?? null,
            'config' => $config ?: null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}
