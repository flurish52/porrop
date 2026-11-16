<?php

namespace App\Services;

use App\Models\Vendor;

class CommissionCalculator
{
    /**
     * fee = clamp(subtotal * rate%, min, max), never more than the subtotal itself.
     *
     * @return array{rate: float, commission: float, payout: float}
     */
    public function forSubtotal(float $subtotal, ?Vendor $vendor = null): array
    {
        $rate = $vendor ? $vendor->commissionRate() : (float) config('porrop.commission.rate');
        $min = (float) config('porrop.commission.min');
        $max = (float) config('porrop.commission.max');

        if ($subtotal <= 0) {
            return ['rate' => $rate, 'commission' => 0.0, 'payout' => 0.0];
        }

        $fee = $subtotal * $rate / 100;
        $fee = min(max($fee, $min), $max);
        $fee = min($fee, $subtotal);
        $fee = round($fee, 2);

        return [
            'rate' => $rate,
            'commission' => $fee,
            'payout' => round($subtotal - $fee, 2),
        ];
    }
}
