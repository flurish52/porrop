<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private CommissionCalculator $commission,
        private PaystackService $paystack,
    ) {}

    /**
     * Create a pending order + payment for one vendor, then start Paystack.
     *
     * $customer: full_name, email, phone, fulfillment_type (delivery|pickup), delivery_address?, pickup_point_id?, user_id?
     * $lines: [['variant_id' => int, 'quantity' => int], ...]
     *
     * @return array{order: Order, payment: Payment, authorization_url: string}
     */
    public function checkout(Vendor $vendor, array $customer, array $lines): array
    {
        if (empty($lines)) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }
        $split = config('porrop.payout_mode') === 'split';

        if ($split && ! $vendor->paystack_subaccount_code) {
            throw ValidationException::withMessages(['cart' => 'This store is not ready to take payments yet. Please message the seller.']);
        }

        [$order, $payment] = DB::transaction(function () use ($vendor, $customer, $lines) {
            $variantIds = collect($lines)->pluck('variant_id')->all();

            // Only this vendor's live products can be bought: a foreign or draft variant id is rejected.
            $variants = ProductVariant::withoutGlobalScope('vendor')
                ->where('vendor_id', $vendor->id)
                ->whereIn('id', $variantIds)
                ->with('product')
                ->get()
                ->keyBy('id');

            $subtotal = 0.0;
            $items = [];

            foreach ($lines as $line) {
                $variant = $variants->get($line['variant_id']);
                $qty = (int) $line['quantity'];

                if (! $variant || ! $variant->product || $variant->product->status !== 'active' || $qty < 1) {
                    throw ValidationException::withMessages(['cart' => 'An item in your cart is no longer available.']);
                }

                if ($variant->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'cart' => "Only {$variant->stock_quantity} left of {$variant->product->name}.",
                    ]);
                }

                $price = (float) $variant->price;
                $subtotal += $price * $qty;

                $items[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->variant_name,
                    'quantity' => $qty,
                    'price' => $price,
                ];
            }

            $subtotal = round($subtotal, 2);
            $fee = $this->commission->forSubtotal($subtotal, $vendor);

            $order = new Order([
                'vendor_id' => $vendor->id,
                'reference' => $this->newReference(),
                'user_id' => $customer['user_id'] ?? null,
                'guest_email' => $customer['email'],
                'full_name' => $customer['full_name'],
                'guest_phone' => $customer['phone'] ?? null,
                'fulfillment_type' => $customer['fulfillment_type'],
                'pickup_point_id' => $customer['pickup_point_id'] ?? null,
                'delivery_address' => $customer['delivery_address'] ?? null,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal, // delivery fees are not charged by Porrop in v1
                'commission_rate' => $fee['rate'],
                'commission_amount' => $fee['commission'],
                'vendor_payout' => $fee['payout'],
                'payout_status' => Order::PAYOUT_PENDING,
            ]);
            $order->save();

            $order->items()->createMany($items);
            $order->statusHistories()->create(['status' => Order::STATUS_PENDING]);

            $payment = $order->payments()->create([
                'gateway' => 'paystack',
                'reference' => 'PAY-'.Str::upper(Str::random(14)),
                'status' => Payment::STATUS_PENDING,
                'amount' => $order->total_amount,
                'currency' => 'NGN',
            ]);

            return [$order, $payment];
        });

        // Network call stays OUTSIDE the transaction. If it fails the pending order is simply abandoned.
        $chargeKobo = (int) round((float) $order->commission_amount * 100);

        if ($split && $chargeKobo >= $payment->amountInKobo()) {
            throw ValidationException::withMessages(['cart' => 'This order is too small to process. Add more items.']);
        }

        $init = $this->paystack->initialize(
            email: $order->guest_email,
            amountKobo: $payment->amountInKobo(),
            reference: $payment->reference,
            callbackUrl: route('paystack.callback'),
            metadata: ['order_id' => $order->id, 'order_reference' => $order->reference, 'vendor_id' => $vendor->id],
            subaccount: $split ? $vendor->paystack_subaccount_code : null,
            transactionChargeKobo: $split ? $chargeKobo : null,
            bearer: (string) config('porrop.fee_bearer', 'account'),
        );

        return [
            'order' => $order,
            'payment' => $payment,
            'authorization_url' => $init['authorization_url'],
        ];
    }

    /**
     * Mark a payment successful. Safe to call many times (callback + webhook race): only the first wins.
     * $gateway is Paystack's `data` object (verify response or webhook data).
     */
    public function markPaid(Payment $payment, array $gateway): bool
    {
        $flipped = false;

        $ok = DB::transaction(function () use ($payment, $gateway, &$flipped) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->status === Payment::STATUS_SUCCESS) {
                return true; // already processed
            }

            // Trust but verify: right amount, right currency, and Paystack says success.
            $paidKobo = (int) ($gateway['amount'] ?? 0);
            $currency = $gateway['currency'] ?? 'NGN';

            if (($gateway['status'] ?? null) !== 'success'
                || $paidKobo !== $payment->amountInKobo()
                || $currency !== $payment->currency) {
                return false;
            }

            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'channel' => $gateway['channel'] ?? null,
                'gateway_response' => $gateway,
                'paid_at' => now(),
            ]);

            $order = Order::withoutGlobalScope('vendor')->whereKey($payment->order_id)->lockForUpdate()->first();

            if ($order->status === Order::STATUS_PENDING) {
                $order->update(['status' => Order::STATUS_PAID] + (config('porrop.payout_mode') === 'split' ?
                        ['payout_status' => Order::PAYOUT_PAID] : []));
                $order->statusHistories()->create(['status' => Order::STATUS_PAID]);
                $flipped = true;

                // v1: floor at zero. A race can still oversell by a unit; the vendor sorts it out with the buyer.
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::withoutGlobalScope('vendor')
                            ->whereKey($item->product_variant_id)
                            ->update(['stock_quantity' => DB::raw('GREATEST(stock_quantity - '.(int) $item->quantity.', 0)')]);
                    }
                }
            }

            return true;
        });

        // Only the call that really moved the order to paid sends emails.
        if ($ok && $flipped) {
            app(OrderNotifier::class)->paid($payment->order_id);
        }

        return $ok;
    }

    private function newReference(): string
    {
        do {
            $ref = 'PRP-'.Str::upper(Str::random(8));
        } while (Order::withoutGlobalScope('vendor')->where('reference', $ref)->exists());

        return $ref;
    }
}
