<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\Product;
use Inertia\Response;

class DashboardController extends VendorAreaController
{
    public function __invoke(): Response
    {
        $vendor = app('currentVendor'); // already scoped by ResolveVendor

        $paid = [Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_READY, Order::STATUS_COMPLETED];

        return $this->page('Vendor/Dashboard', [
            'vendor' => [
                'name' => $vendor->name,
                'slug' => $vendor->slug,
                'status' => $vendor->status,
                'url' => route('store.show', $vendor),
                'add_product_url' => route('vendor.products.create', ['vendor' => $vendor]),
                'whatsapp_number' => $vendor->whatsapp_number,
                'has_payout_details' => $vendor->hasPayoutDetails(),
            ],
            'stats' => [
                'products' => Product::count(),
                'orders' => Order::whereIn('status', $paid)->count(),
                'earned' => (float) Order::whereIn('status', $paid)->sum('vendor_payout'),
                'awaiting_payout' => (float) Order::whereIn('status', $paid)->where('payout_status', Order::PAYOUT_PENDING)->sum('vendor_payout'),
            ],
        ]);
    }
}
