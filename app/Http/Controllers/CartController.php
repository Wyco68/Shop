<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService)
    {}

    public function index(Request $request)
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $summary = $this->cartService->getCartSummary($cart);

        return view('order_summary', $summary);
    }

    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_LINE_QUANTITY],
        ]);

        $cart = $this->cartService->getOrCreateCart($request->user());

        try {
            $this->cartService->addItem($cart, $request->integer('variant_id'), $request->integer('quantity'));
        } catch (\RuntimeException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        return redirect()->route('cart.index')->with('success', 'Item added to cart.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_LINE_QUANTITY],
        ]);

        $cart = $this->cartService->getOrCreateCart($request->user());

        try {
            $this->cartService->updateQuantity($cart, $request->integer('variant_id'), $request->integer('quantity'));
        } catch (\RuntimeException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        return redirect()->route('cart.index');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
        ]);

        $cart = $this->cartService->getOrCreateCart($request->user());
        $this->cartService->removeItem($cart, $request->integer('variant_id'));

        return redirect()->route('cart.index');
    }
}