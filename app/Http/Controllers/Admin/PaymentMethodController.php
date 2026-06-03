<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', PaymentMethod::class);

        $paymentMethods = PaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.payment-methods.index', compact('paymentMethods'));
    }

    public function create(): View
    {
        $this->authorize('create', PaymentMethod::class);

        return view('admin.payment-methods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PaymentMethod::class);

        $data = $this->validated($request);

        if ($request->hasFile('qr_image')) {
            $data['qr_image_path'] = $request->file('qr_image')->store('payment-methods', config('filesystems.product_disk', 'public'));
        }

        PaymentMethod::create($data);

        return redirect()->route('admin.payment-methods.index')->with('success', 'Payment method created.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        $this->authorize('update', $paymentMethod);

        return view('admin.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorize('update', $paymentMethod);

        $data = $this->validated($request, $paymentMethod);

        if ($request->hasFile('qr_image')) {
            $data['qr_image_path'] = $request->file('qr_image')->store('payment-methods', config('filesystems.product_disk', 'public'));
        }

        $paymentMethod->update($data);

        return redirect()->route('admin.payment-methods.index')->with('success', 'Payment method updated.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorize('delete', $paymentMethod);

        if ($paymentMethod->orders()->exists()) {
            return back()->with('error', 'Cannot delete a payment method used by existing orders.');
        }

        $paymentMethod->delete();

        return redirect()->route('admin.payment-methods.index')->with('success', 'Payment method deleted.');
    }

    private function validated(Request $request, ?PaymentMethod $existing = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('payment_methods', 'code')->ignore($existing?->id)],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'qr_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $code = $validated['code'] ?? Str::slug($validated['name'], '_');

        return [
            'name' => $validated['name'],
            'code' => $code,
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}
