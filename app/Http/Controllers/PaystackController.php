<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\OrderService;
use App\Services\PaystackException;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    public function __construct(
        private PaystackService $paystack,
        private OrderService $orders,
    ) {}

    /** Customer returns from Paystack. We never trust the query string: ask Paystack. */
    public function callback(Request $request): RedirectResponse
    {
        $reference = (string) ($request->query('reference') ?? $request->query('trxref'));

        $payment = Payment::where('reference', $reference)->with('order.vendor')->first();

        if (! $payment) {
            return redirect()->route('home')->with('error', 'We could not find that payment.');
        }

        $vendor = $payment->order->vendor;

        try {
            $data = $this->paystack->verify($reference);
            $ok = $this->orders->markPaid($payment, $data);
        } catch (PaystackException $e) {
            Log::warning('Paystack verify failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            $ok = false;
        }

        if ($ok) {
            session()->forget('cart.'.$vendor->id);
        }

        return redirect()
            ->route('store.order', ['vendor' => $vendor, 'reference' => $payment->order->reference])
            ->with($ok ? 'success' : 'error', $ok
                ? 'Payment received. Your order '.$payment->order->reference.' is confirmed.'
                : 'We could not confirm your payment yet. If you were debited, it will update shortly.');
    }

    /** Server-to-server. Always 200 once the signature is good, so Paystack stops retrying. */
    public function webhook(Request $request): Response
    {
        if (! $this->paystack->validSignature($request->getContent(), $request->header('x-paystack-signature'))) {
            return response('invalid signature', 401);
        }

        $event = $request->json('event');
        $data = (array) $request->json('data', []);

        if ($event === 'charge.success' && isset($data['reference'])) {
            $payment = Payment::where('reference', $data['reference'])->first();

            if ($payment) {
                try {
                    $this->orders->markPaid($payment, $data);
                } catch (\Throwable $e) {
                    Log::error('Webhook markPaid failed', ['reference' => $data['reference'], 'error' => $e->getMessage()]);
                    return response('retry', 500); // let Paystack retry real failures
                }
            }
        }

        return response('ok', 200);
    }
}
