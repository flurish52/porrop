<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Base for pages inside /store/{vendor}/dashboard. Adds the nav props the VendorLayout reads. */
abstract class VendorAreaController extends Controller
{
    protected function page(string $component, array $props = []): Response
    {
        $vendor = app('currentVendor');

        return Inertia::render($component, $props + [
            'nav' => [
                'name' => $vendor->name,
                'dashboard' => route('vendor.dashboard', ['vendor' => $vendor]),
                'products' => route('vendor.products.index', ['vendor' => $vendor]),
                'orders' => route('vendor.orders.index', ['vendor' => $vendor]),
                'payout' => route('vendor.payout.edit', ['vendor' => $vendor]),
                'store' => route('store.show', ['vendor' => $vendor]),

            ],
        ]);
    }
}
