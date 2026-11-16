<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class OrderController extends VendorAreaController
{
    /** What a vendor may move an order to from each status. Forward only: refunds/cancels go through Porrop support. */
    private const NEXT = [
        Order::STATUS_PAID => [Order::STATUS_PROCESSING, Order::STATUS_READY, Order::STATUS_COMPLETED],
        Order::STATUS_PROCESSING => [Order::STATUS_READY, Order::STATUS_COMPLETED],
        Order::STATUS_READY => [Order::STATUS_COMPLETED],
    ];

    public function index(): Response
    {
        $vendor = app('currentVendor');

        // Unpaid (pending) orders are just abandoned checkouts, so they are not listed.
        $orders = Order::withCount('items')
            ->where('status', '!=', Order::STATUS_PENDING)
            ->latest()
            ->paginate(20)
            ->through(fn (Order $o) => [
                'id' => $o->id,
                'reference' => $o->reference,
                'status' => $o->status,
                'customer' => $o->full_name,
                'items' => $o->items_count,
                'total' => (float) $o->total_amount,
                'payout' => (float) $o->vendor_payout,
                'placed_at' => $o->created_at->format('j M, g:ia'),
                'url' => route('vendor.orders.show', ['vendor' => $vendor, 'order' => $o]),
            ]);

        return $this->page('Vendor/Orders/Index', ['orders' => $orders]);
    }

    public function show(Order $order): Response
    {
        $vendor = app('currentVendor');
        $order->load('items');

        return $this->page('Vendor/Orders/Show', [
            'order' => [
                'reference' => $order->reference,
                'status' => $order->status,
                'placed_at' => $order->created_at->format('j M Y, g:ia'),
                'customer' => [
                    'name' => $order->full_name,
                    'email' => $order->guest_email,
                    'phone' => $order->guest_phone,
                    'address' => $order->delivery_address,
                ],
                'items' => $order->items->map(fn ($i) => [
                    'name' => $i->product_name,
                    'variant_name' => $i->variant_name === 'Default' ? null : $i->variant_name,
                    'quantity' => $i->quantity,
                    'price' => (float) $i->price,
                ])->values(),
                'subtotal' => (float) $order->subtotal,
                'commission' => (float) $order->commission_amount,
                'commission_rate' => (float) $order->commission_rate,
                'payout' => (float) $order->vendor_payout,
                'payout_status' => $order->payout_status,
            ],
            'next_statuses' => self::NEXT[$order->status] ?? [],
            'status_url' => route('vendor.orders.status', ['vendor' => $vendor, 'order' => $order]),
            'back_url' => route('vendor.orders.index', ['vendor' => $vendor]),
        ]);
    }

    public function status(Request $request, Order $order): RedirectResponse
    {
        $allowed = self::NEXT[$order->status] ?? [];

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', $allowed ?: ['none'])],
        ], [
            'status.in' => 'That status change is not allowed for this order.',
        ]);

        $order->update(['status' => $data['status']]);
        $order->statusHistories()->create(['status' => $data['status'], 'changed_by' => $request->user()->id]);

        return back()->with('success', 'Order updated.');
    }
}
