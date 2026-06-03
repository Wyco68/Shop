<div class="space-y-5">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
        <input type="text" name="name" value="{{ old('name', $paymentMethod?->name ?? '') }}" required
               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
        @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Code (optional)</label>
        <input type="text" name="code" value="{{ old('code', $paymentMethod?->code ?? '') }}" placeholder="auto-generated from name"
               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-sky-500">
        @error('code')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Instructions</label>
        <textarea name="instructions" rows="6"
                  class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-sky-500">{{ old('instructions', $paymentMethod?->instructions ?? '') }}</textarea>
        <p class="text-xs text-slate-500 mt-1">Shown to customers after they select this method (account numbers, transfer steps, etc.).</p>
        @error('instructions')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Sort order</label>
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $paymentMethod?->sort_order ?? 0) }}"
                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm">
        </div>
        <div class="flex items-end pb-2">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-sky-600"
                       @checked(old('is_active', $paymentMethod?->is_active ?? true))>
                Active at checkout
            </label>
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">QR code image (optional)</label>
        <x-admin.media-upload
            name="qr_image"
            id="payment_method_qr"
            :preview="!empty($paymentMethod?->qr_image_path) ? $paymentMethod->qrImageUrl() : ''"
            accept="image/png,image/jpeg,image/webp,image/gif"
            placeholder="Upload QR code"
            :button="!empty($paymentMethod) ? 'Update QR image' : 'Choose QR image'"
            :hint="!empty($paymentMethod) ? 'Leave empty to keep current image' : 'Shown to customers at checkout'"
            height="h-40"
            object-class="object-contain"
            preview-rounded="rounded-lg"
        />
    </div>
</div>
