<?php

namespace App\Services;

use App\Mail\NewOrderForVendor;
use App\Mail\OrderReceipt;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails that go out when an order is paid. Every send is wrapped: a broken mail setup must NEVER
 * make payment confirmation fail (Paystack would retry the webhook and the customer would see an error
 * for money they already paid).
 */
class OrderNotifier
{
    public function paid(int $orderId): void
    {
        $order = Order::with(['items', 'vendor.users'])->find($orderId);

        if (! $order || ! $order->vendor) {
            return;
        }

        $vendor = $order->vendor;

        $vendorEmails = $vendor->users->pluck('email')->push($vendor->email)->filter()->unique()->values()->all();

        $this->send($vendorEmails, fn () => new NewOrderForVendor(
            $order, $vendor, route('vendor.orders.show', ['vendor' => $vendor, 'order' => $order])
        ), 'vendor alert', $order);

        $this->send($order->guest_email ? [$order->guest_email] : [], fn () => new OrderReceipt(
            $order, $vendor, route('store.order', ['vendor' => $vendor, 'reference' => $order->reference])
        ), 'customer receipt', $order);
    }

    private function send(array $to, \Closure $mailable, string $what, Order $order): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mailable());
        } catch (\Throwable $e) {
            Log::warning("Order email failed ({$what})", ['order' => $order->reference, 'error' => $e->getMessage()]);
        }
    }
}
