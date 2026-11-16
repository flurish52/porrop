<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Vendor;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Platform admin only (route middleware). Not tenant-scoped on purpose: it sees every store. */
class PayoutController extends Controller
{
    public function __construct(private PayoutService $payouts) {}

    public function index(): Response
    {
        $pending = Order::where('payout_status', Order::PAYOUT_PENDING)
            ->whereIn('status', PayoutService::COUNTED)
            ->with('payments')
            ->get()
            ->groupBy('vendor_id');

        $vendors = Vendor::whereIn('id', $pending->keys())->get()->keyBy('id');

        $rows = $pending->map(function ($orders, $vendorId) use ($vendors) {
            $vendor = $vendors->get($vendorId);
            $ready = $this->payouts->eligible($orders);
            $held = $orders->diff($ready);

            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'store_url' => route('store.show', ['vendor' => $vendor]),
                'owed' => round((float) $ready->sum('vendor_payout'), 2),
                'owed_orders' => $ready->count(),
                'held' => round((float) $held->sum('vendor_payout'), 2),
                'held_orders' => $held->count(),
                'has_details' => $vendor->hasPayoutDetails(),
                'bank_name' => $vendor->bank_name,
                'account_number' => $vendor->account_number,
                'account_name' => $vendor->account_name,
                'pay_url' => route('admin.payouts.store', ['vendor' => $vendor]),
            ];
        })
            ->filter(fn ($r) => $r['owed_orders'] > 0 || $r['held_orders'] > 0)
            ->sortByDesc('owed')
            ->values();

        $history = Payout::with(['vendor', 'paidBy'])->latest('paid_at')->limit(20)->get()->map(fn (Payout $p) => [
            'id' => $p->id,
            'vendor' => $p->vendor->name,
            'amount' => (float) $p->amount,
            'order_count' => $p->order_count,
            'reference' => $p->reference,
            'paid_at' => $p->paid_at->format('j M Y, g:ia'),
            'by' => $p->paidBy?->name,
        ]);

        return Inertia::render('Admin/Payouts/Index', [
            'vendors' => $rows,
            'history' => $history,
            'holdDays' => (int) config('porrop.payout_hold_days', 3),
        ]);
    }

    public function store(Request $request, Vendor $vendor): RedirectResponse
    {
        $data = $request->validate([
            'expected_amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $payout = $this->payouts->payOut($vendor, $request->user(), (float) $data['expected_amount'], $data['reference'] ?? null);

        return back()->with('success', 'Recorded ₦'.number_format((float) $payout->amount, 2)." paid to {$vendor->name} ({$payout->order_count} orders).");
    }
}
