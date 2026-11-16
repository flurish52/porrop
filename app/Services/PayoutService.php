<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payout;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayoutService
{
    /** Orders that count toward a payout at all: paid by the customer and not yet settled with the vendor. */
    public const COUNTED = [Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_READY, Order::STATUS_COMPLETED];

    /**
     * The payout rule, in one place. An order is ready to pay out when the vendor marked it completed,
     * OR the customer's payment is older than PORROP_PAYOUT_HOLD_DAYS (so a vendor who forgets to click
     * "completed" still gets paid). Everything else is "on hold".
     *
     * @param  Collection<int, Order>  $orders  with their `payments` loaded
     * @return Collection<int, Order>
     */
    public function eligible(Collection $orders): Collection
    {
        $cutoff = now()->subDays((int) config('porrop.payout_hold_days', 3));

        return $orders->filter(function (Order $o) use ($cutoff) {
            if ($o->status === Order::STATUS_COMPLETED) {
                return true;
            }

            $paidAt = $o->payments->where('status', 'success')->max('paid_at');

            return $paidAt && $paidAt <= $cutoff;
        })->values();
    }

    /**
     * Record a transfer you just made: settles every ready order of the vendor in one go.
     * $expected is the amount the admin saw on screen. If it changed since (a new order became
     * eligible), refuse, so you never send money for a different total than you confirmed.
     */
    public function payOut(Vendor $vendor, User $admin, float $expected, ?string $reference): Payout
    {
        if (! $vendor->hasPayoutDetails()) {
            throw ValidationException::withMessages(['payout' => "{$vendor->name} has not added bank details yet."]);
        }

        return DB::transaction(function () use ($vendor, $admin, $expected, $reference) {
            $orders = Order::where('vendor_id', $vendor->id)
                ->where('payout_status', Order::PAYOUT_PENDING)
                ->whereIn('status', self::COUNTED)
                ->lockForUpdate()
                ->with('payments')
                ->get();

            $ready = $this->eligible($orders);

            if ($ready->isEmpty()) {
                throw ValidationException::withMessages(['payout' => 'Nothing is ready to pay out for this vendor.']);
            }

            $amount = round((float) $ready->sum('vendor_payout'), 2);

            if (abs($amount - round($expected, 2)) > 0.001) {
                throw ValidationException::withMessages(['payout' => 'The amount changed since you opened this page. Refresh and check it again.']);
            }

            $payout = Payout::create([
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'order_count' => $ready->count(),
                'reference' => $reference ?: null,
                'paid_by' => $admin->id,
                'paid_at' => now(),
            ]);

            Order::whereIn('id', $ready->pluck('id'))->update([
                'payout_status' => Order::PAYOUT_PAID,
                'payout_id' => $payout->id,
            ]);

            return $payout;
        });
    }
}
