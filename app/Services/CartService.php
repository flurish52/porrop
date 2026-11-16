<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Vendor;

/**
 * Session cart, one per store: session key cart.{vendor_id} => [variant_id => quantity].
 * Only ids and quantities live in the session. Names and prices are read fresh from the database
 * every time, so a price change or a sold-out item can never be hidden by an old cart.
 */
class CartService
{
    private function key(Vendor $v): string
    {
        return 'cart.'.$v->id;
    }

    /** @return array<int,int> */
    public function raw(Vendor $v): array
    {
        return (array) session($this->key($v), []);
    }

    public function count(Vendor $v): int
    {
        return (int) array_sum($this->raw($v));
    }

    /** Adds to what is already there, capped at stock. Returns the quantity now in the cart. */
    public function add(Vendor $v, ProductVariant $variant, int $qty): int
    {
        $cart = $this->raw($v);
        $new = min(($cart[$variant->id] ?? 0) + max($qty, 1), (int) $variant->stock_quantity);

        if ($new < 1) {
            return 0;
        }

        $cart[$variant->id] = $new;
        session([$this->key($v) => $cart]);

        return $new;
    }

    public function set(Vendor $v, ProductVariant $variant, int $qty): void
    {
        $cart = $this->raw($v);
        $qty = min($qty, (int) $variant->stock_quantity);

        if ($qty < 1) {
            unset($cart[$variant->id]);
        } else {
            $cart[$variant->id] = $qty;
        }

        session([$this->key($v) => $cart]);
    }

    public function remove(Vendor $v, int $variantId): void
    {
        $cart = $this->raw($v);
        unset($cart[$variantId]);
        session([$this->key($v) => $cart]);
    }

    public function clear(Vendor $v): void
    {
        session()->forget($this->key($v));
    }

    /** @return list<array{variant_id:int,quantity:int}> what OrderService::checkout expects */
    public function forCheckout(Vendor $v): array
    {
        return collect($this->summary($v)['lines'])
            ->map(fn ($l) => ['variant_id' => $l['variant_id'], 'quantity' => $l['quantity']])
            ->all();
    }

    /** Everything the cart drawer, sticky bar and header badge need. Needs currentVendor bound. */
    public function panel(Vendor $v): array
    {
        $summary = $this->summary($v);

        $lines = array_map(fn ($l) => $l + [
                'update_url' => route('store.cart.update', ['vendor' => $v, 'variant' => $l['variant_id']]),
                'remove_url' => route('store.cart.remove', ['vendor' => $v, 'variant' => $l['variant_id']]),
            ], $summary['lines']);

        return [
            'count' => (int) array_sum(array_column($lines, 'quantity')),
            'subtotal' => $summary['subtotal'],
            'lines' => $lines,
            'quantities' => (object) array_column($lines, 'quantity', 'variant_id'),
            'cart_url' => route('store.cart', ['vendor' => $v]),
            'checkout_url' => route('store.checkout', ['vendor' => $v]),
        ];
    }
    /**
     * Cart as the customer sees it. Items that are gone, hidden or sold out are dropped, and quantities
     * above stock are lowered. `changed` tells the page to show a notice. Needs currentVendor bound.
     *
     * @return array{lines: list<array>, subtotal: float, changed: bool}
     */
    public function summary(Vendor $v): array
    {
        $cart = $this->raw($v);

        if (! $cart) {
            return ['lines' => [], 'subtotal' => 0.0, 'changed' => false];
        }

        $variants = ProductVariant::with('product.images')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $lines = [];
        $clean = [];
        $subtotal = 0.0;
        $changed = false;

        foreach ($cart as $id => $qty) {
            $variant = $variants->get($id);

            if (! $variant || ! $variant->product || $variant->product->status !== 'active' || $variant->stock_quantity < 1) {
                $changed = true;
                continue;
            }

            $q = min((int) $qty, (int) $variant->stock_quantity);
            $changed = $changed || $q !== (int) $qty;
            $clean[$id] = $q;

            $price = (float) $variant->price;
            $images = $variant->product->images;
            $image = $images->firstWhere('product_variant_id', $variant->id) ?? $images->firstWhere('is_primary', true) ?? $images->first();

            $lines[] = [
                'variant_id' => (int) $id,
                'name' => $variant->product->name,
                'variant_name' => $variant->variant_name === 'Default' ? null : $variant->variant_name,
                'price' => $price,
                'quantity' => $q,
                'stock' => (int) $variant->stock_quantity,
                'total' => round($price * $q, 2),
                'image' => $image?->url,
                'product_url' => route('store.product', ['vendor' => $v, 'product' => $variant->product]),
            ];

            $subtotal += $price * $q;
        }

        if ($changed) {
            session([$this->key($v) => $clean]);
        }

        return ['lines' => $lines, 'subtotal' => round($subtotal, 2), 'changed' => $changed];
    }
}
