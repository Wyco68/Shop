<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartService
{
    public const MAX_LINE_QUANTITY = 99;

    public function getOrCreateCart(?User $user, ?string $sessionId = null): Cart
    {
        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    public function addItem(Cart $cart, int $variantId, int $quantity = 1): CartItem
    {
        $variant = $this->resolvePurchasableVariant($variantId);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('variant_id', $variantId)
            ->first();

        $newQuantity = $this->capLineQuantity($variant, ($item?->quantity ?? 0) + $quantity);

        if ($item) {
            $item->update(['quantity' => $newQuantity]);

            return $item->fresh();
        }

        return CartItem::create([
            'cart_id' => $cart->id,
            'variant_id' => $variantId,
            'quantity' => $newQuantity,
        ]);
    }

    public function updateQuantity(Cart $cart, int $variantId, int $quantity): CartItem
    {
        $variant = $this->resolvePurchasableVariant($variantId);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('variant_id', $variantId)
            ->firstOrFail();

        $item->update(['quantity' => $this->capLineQuantity($variant, $quantity)]);

        return $item->fresh();
    }

    public function removeItem(Cart $cart, int $variantId): void
    {
        CartItem::where('cart_id', $cart->id)
            ->where('variant_id', $variantId)
            ->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function mergeGuestCart(string $sessionId, User $user): Cart
    {
        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);
        $guestCart = Cart::where('session_id', $sessionId)->first();

        if (!$guestCart) {
            return $userCart;
        }

        DB::transaction(function () use ($userCart, $guestCart) {
            foreach ($guestCart->items as $guestItem) {
                if (! $this->isVariantPurchasable($guestItem->variant_id)) {
                    continue;
                }

                $existing = $userCart->items()
                    ->where('variant_id', $guestItem->variant_id)
                    ->first();

                $variant = ProductVariant::with(['product', 'inventory'])->find($guestItem->variant_id);

                if (! $variant) {
                    continue;
                }

                $mergedQuantity = $this->capLineQuantity(
                    $variant,
                    ($existing?->quantity ?? 0) + $guestItem->quantity,
                );

                if ($existing) {
                    $existing->update(['quantity' => $mergedQuantity]);
                } else {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'variant_id' => $guestItem->variant_id,
                        'quantity' => $mergedQuantity,
                    ]);
                }
            }

            $guestCart->items()->delete();
            $guestCart->delete();
        });

        return $userCart->fresh(['items.variant.product']);
    }

    /**
     * Reject checkout when the cart contains inactive, archived, or missing variants.
     *
     * @throws \RuntimeException
     */
    public function validateCartForCheckout(Cart $cart): void
    {
        $cart->load('items.variant.product', 'items.variant.inventory');

        foreach ($cart->items as $cartItem) {
            if (! $cartItem->variant) {
                throw new \RuntimeException('Your cart contains an item that is no longer available.');
            }

            $this->assertVariantPurchasable($cartItem->variant);

            $maxAllowed = min(self::MAX_LINE_QUANTITY, $cartItem->variant->available_stock);

            if ($cartItem->quantity > $maxAllowed) {
                throw new \RuntimeException(
                    "Only {$maxAllowed} of {$cartItem->variant->product->name} are available."
                );
            }
        }
    }

    public function getCartSummary(Cart $cart): array
    {
        $this->removeUnavailableItems($cart);

        $cart->load(
            'items.variant.product.discounts',
            'items.variant.product.category.discounts',
            'items.variant.inventory',
        );

        $items = [];
        $subtotal = 0;
        $discountTotal = 0;

        foreach ($cart->items as $cartItem) {
            $variant = $cartItem->variant;
            $product = $variant->product;
            $price = $variant->effective_price;
            $lineTotal = $price * $cartItem->quantity;

            $discount = $this->calculateDiscount($product, $cartItem->quantity, $price);
            $discountAmount = $discount['amount'];
            $finalLineTotal = $lineTotal - $discountAmount;

            $items[] = [
                'cart_item' => $cartItem,
                'variant' => $variant,
                'product' => $product,
                'unit_price' => $price,
                'quantity' => $cartItem->quantity,
                'line_total' => $lineTotal,
                'discount_amount' => $discountAmount,
                'discount_info' => $discount['info'],
                'final_line_total' => $finalLineTotal,
                'available_stock' => $variant->available_stock,
            ];

            $subtotal += $lineTotal;
            $discountTotal += $discountAmount;
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total' => $subtotal - $discountTotal,
        ];
    }

    public function resolvePurchasableVariant(int $variantId): ProductVariant
    {
        $variant = ProductVariant::with(['product', 'inventory'])->find($variantId);

        if (! $variant) {
            throw new \RuntimeException('This product is no longer available.');
        }

        $this->assertVariantPurchasable($variant);

        return $variant;
    }

    public function isVariantPurchasable(int $variantId): bool
    {
        $variant = ProductVariant::with(['product', 'inventory'])->find($variantId);

        return $variant !== null && $this->isVariantModelPurchasable($variant);
    }

    private function isVariantModelPurchasable(ProductVariant $variant): bool
    {
        try {
            $this->assertVariantPurchasable($variant);
        } catch (\RuntimeException) {
            return false;
        }

        return true;
    }

    /**
     * Uses the relation already loaded below instead of isVariantPurchasable()'s
     * fresh per-ID query — this runs once per cart line on every cart view and
     * checkout, so a query per item here is an N+1 on the same hot path as
     * calculateDiscount()/bestDiscount() above.
     */
    private function removeUnavailableItems(Cart $cart): void
    {
        $cart->loadMissing('items.variant.product');

        foreach ($cart->items as $cartItem) {
            if (! $cartItem->variant || ! $this->isVariantModelPurchasable($cartItem->variant)) {
                $cartItem->delete();
            }
        }
    }

    private function capLineQuantity(ProductVariant $variant, int $requestedQuantity): int
    {
        $stockCap = $variant->available_stock;
        $maxAllowed = min(self::MAX_LINE_QUANTITY, $stockCap);

        if ($maxAllowed <= 0) {
            throw new \RuntimeException('This item is out of stock.');
        }

        return min($requestedQuantity, $maxAllowed);
    }

    private function assertVariantPurchasable(ProductVariant $variant): void
    {
        $product = $variant->product;

        if (! $product || $product->trashed()) {
            throw new \RuntimeException('This product is no longer available.');
        }

        if (! $product->is_active || ! $variant->is_active) {
            throw new \RuntimeException("{$product->name} is no longer available for purchase.");
        }
    }

    /**
     * Reads from the relations eager-loaded by getCartSummary() instead of
     * querying — this runs once per cart line, so a fresh query here turns
     * into an N+1 on every cart view and checkout.
     */
    private function calculateDiscount($product, int $quantity, float $unitPrice): array
    {
        $discount = $this->bestDiscount($product->discounts, $quantity)
            ?? ($product->category ? $this->bestDiscount($product->category->discounts, $quantity) : null);

        if (!$discount) {
            return ['amount' => 0, 'info' => null];
        }

        $lineTotal = $unitPrice * $quantity;

        if ($discount->type === 'percentage') {
            $amount = $lineTotal * ($discount->value / 100);
        } else {
            $amount = min($discount->value * $quantity, $lineTotal);
        }

        return [
            'amount' => round($amount, 2),
            'info' => [
                'name' => $discount->name,
                'type' => $discount->type,
                'value' => $discount->value,
            ],
        ];
    }

    /**
     * In-memory equivalent of Discount::active()->where('min_quantity', '<=', $quantity)->orderByDesc('value')->first(),
     * applied to an already-loaded relation collection instead of issuing a query.
     */
    private function bestDiscount(Collection $discounts, int $quantity): ?Discount
    {
        return $discounts
            ->filter(fn (Discount $discount) => $discount->min_quantity <= $quantity && $discount->isActiveNow())
            ->sortByDesc('value')
            ->first();
    }
}
