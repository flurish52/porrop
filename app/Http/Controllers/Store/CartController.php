<?php

namespace App\Http\Controllers\Store;

use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CartController extends StoreAreaController
{
    public function __construct(private CartService $cart) {}

    public function show(): Response
    {
        $vendor = app('currentVendor');
        $summary = $this->cart->summary($vendor);

        $lines = array_map(fn ($l) => $l + [
                'update_url' => route('store.cart.update', ['vendor' => $vendor, 'variant' => $l['variant_id']]),
                'remove_url' => route('store.cart.remove', ['vendor' => $vendor, 'variant' => $l['variant_id']]),
            ], $summary['lines']);

        return $this->page('Store/Cart', [
            'lines' => $lines,
            'subtotal' => $summary['subtotal'],
            'notice' => $summary['changed'] ? 'We updated your cart because some items changed.' : null,
            'checkoutUrl' => route('store.checkout', ['vendor' => $vendor]),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $vendor = app('currentVendor');

        // The vendor scope means another store's variant id is simply "not found".
        $variant = ProductVariant::with('product')->findOrFail($data['variant_id']);
        abort_unless($variant->product && $variant->product->status === 'active', 404);

        $have = $this->cart->raw($vendor)[$variant->id] ?? 0;
        $now = $this->cart->add($vendor, $variant, $data['quantity']);

        if ($now < 1) {
            return back()->with('error', 'Sorry, that one is sold out.');
        }

        if ($now <= $have) {
            return back()->with('error', 'You already have all that is available in your cart.');
        }

        // Stay on the page: the cart badge, bar and drawer update from the fresh props.
        return back();
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);

        $this->cart->set(app('currentVendor'), $variant, $data['quantity']);

        return back();
    }

    public function remove(ProductVariant $variant): RedirectResponse
    {
        $this->cart->remove(app('currentVendor'), $variant->id);

        return back();
    }
}
