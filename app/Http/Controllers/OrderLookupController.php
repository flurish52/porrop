<?php

namespace App\Http\Controllers;

use App\Mail\OrderLink;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Find my order": the buyer gives the email they used and the PRP- reference from their receipt.
 * If both match one order, the private link is EMAILED to that address. The page never shows the order
 * and always gives the same answer, so nobody can use it to find out whose email has orders.
 */
class OrderLookupController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Orders/Find');
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'reference' => ['required', 'string', 'max:30'],
        ], [
            'email.required' => 'Enter the email you used when you ordered.',
            'reference.required' => 'Enter your order reference. It starts with PRP-.',
        ]);

        $email = Str::lower(trim($data['email']));
        $reference = Str::upper(trim($data['reference']));

        // A few tries per email address per 10 minutes, on top of the per-IP throttle on the route.
        $key = 'order-find:'.sha1($email);

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 600);

            // Not inside a store, so no tenant scope is active: look across all stores on purpose.
            $order = Order::withoutGlobalScope('vendor')
                ->with('vendor')
                ->where('reference', $reference)
                ->whereRaw('LOWER(guest_email) = ?', [$email])
                ->first();

            if ($order && $order->vendor) {
                try {
                    Mail::to($order->guest_email)->send(new OrderLink(
                        $order,
                        $order->vendor,
                        // The route parameter is called "reference" but carries the long private token.
                        route('store.order', ['vendor' => $order->vendor, 'reference' => $order->public_token]),
                    ));
                } catch (\Throwable $e) {
                    Log::warning('Order link email failed', ['order' => $order->reference, 'error' => $e->getMessage()]);
                }
            }
        }

        return back()->with('success', 'If those details match an order, we have emailed the link to that address. Check your inbox and your spam folder.');
    }
}
