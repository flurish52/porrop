<?php

namespace App\Http\Controllers\Store;

use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaystackException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends StoreAreaController
{
    public function __construct(private CartService $cart, private OrderService $orders) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $vendor = app('currentVendor');
        $summary = $this->cart->summary($vendor);

        if (! $summary['lines']) {
            return redirect()->route('store.cart', ['vendor' => $vendor]);
        }

        $user = $request->user();

        return $this->page('Store/Checkout', [
            'lines' => $summary['lines'],
            'subtotal' => $summary['subtotal'],
            'notice' => $summary['changed'] ? 'We updated your cart because some items changed.' : null,
            'submitUrl' => route('store.checkout.store', ['vendor' => $vendor]),
            'prefill' => ['full_name' => $user?->name ?? '', 'email' => $user?->email ?? '', 'phone' => $user?->phone ?? ''],
        ]);
    }

    public function store(Request $request): SymfonyResponse|RedirectResponse
    {
        $vendor = app('currentVendor');

        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'regex:/^234\d{10}$/'],
            'delivery_address' => ['required', 'string', 'min:8', 'max:500'],
        ], [
            'phone.regex' => 'Enter a Nigerian phone number like 0803 123 4567.',
            'delivery_address.min' => 'Please enter your full delivery address.',
        ]);

        // Lines come from the server-side cart, never from the browser.
        $lines = $this->cart->forCheckout($vendor);

        try {
            $result = $this->orders->checkout($vendor, [
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'fulfillment_type' => 'delivery',
                'delivery_address' => $data['delivery_address'],
                'user_id' => $request->user()?->id,
            ], $lines);
        } catch (PaystackException $e) {
            Log::warning('Paystack initialize failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['payment' => 'We could not start the payment. Please try again in a moment.']);
        }

        // Full-page redirect to Paystack (works for both Inertia and plain requests).
        return Inertia::location($result['authorization_url']);
    }

    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        return preg_match('/^0\d{10}$/', $digits) ? '234'.substr($digits, 1) : $digits;
    }
}
