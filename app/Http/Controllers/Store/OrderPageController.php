<?php

namespace App\Http\Controllers\Store;

use App\Models\Order;
use Inertia\Response;

/**
 * The customer's order page. Needs no login: the long private token in the link is the key.
 * (The short PRP- reference is only a label and no longer opens this page.)
 * We never show commission here.
 */
class OrderPageController extends StoreAreaController
{
    // The route parameter is still named {reference}; it now carries the order's public_token.
    public function show(string $reference): Response
    {
        // Scoped to this store, so a token from another store is a 404.
        $order = Order::with('items')->where('public_token', $reference)->firstOrFail();

        return $this->page('Store/Order', [
            'order' => [
                'reference' => $order->reference,
                'status' => $order->status,
                'placed_at' => $order->created_at->format('j M Y, g:ia'),
                'full_name' => $order->full_name,
                'phone' => $order->guest_phone,
                'delivery_address' => $order->delivery_address,
                'total' => (float) $order->total_amount,
                'items' => $order->items->map(fn ($i) => [
                    'name' => $i->product_name,
                    'variant_name' => $i->variant_name === 'Default' ? null : $i->variant_name,
                    'quantity' => $i->quantity,
                    'price' => (float) $i->price,
                ])->values(),
            ],
        ]);
    }
}
