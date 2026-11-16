<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Inertia\Inertia;
use Inertia\Response;

/** Base for customer-facing pages inside /store/{vendor}. Adds the `shop` prop the StoreLayout reads. */
abstract class StoreAreaController extends Controller
{
    protected function page(string $component, array $props = []): Response
    {
        $vendor = app('currentVendor');

        return Inertia::render($component, $props + [
            'shop' => [
                'name' => $vendor->name,
                'url' => route('store.show', ['vendor' => $vendor]),
                'cart_url' => route('store.cart', ['vendor' => $vendor]),
                'cart' => app(CartService::class)->panel($vendor),
                'whatsapp_number' => $vendor->whatsapp_number,
                'logo' => $vendor->logo_url,
            ],
        ]);
    }
}
