<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['paystack.secret_key' => self::SECRET]);
    }

    private function pendingPayment(): array
    {
        $vendor = Vendor::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $product = Product::create(['vendor_id' => $vendor->id, 'name' => 'Bag', 'slug' => 'bag', 'status' => 'active']);
        $variant = ProductVariant::create(['vendor_id' => $vendor->id, 'product_id' => $product->id, 'variant_name' => 'Default', 'sku' => 'BAG-1', 'price' => 12000, 'stock_quantity' => 5]);

        $order = Order::create([
            'vendor_id' => $vendor->id, 'reference' => 'PRP-TEST0001', 'guest_email' => 'c@example.com',
            'full_name' => 'Customer', 'fulfillment_type' => 'delivery', 'status' => 'pending',
            'subtotal' => 12000, 'total_amount' => 12000, 'commission_rate' => 10,
            'commission_amount' => 1200, 'vendor_payout' => 10800,
        ]);
        $order->items()->create(['product_variant_id' => $variant->id, 'product_name' => 'Bag', 'variant_name' => 'Default', 'quantity' => 2, 'price' => 6000]);
        $payment = $order->payments()->create(['gateway' => 'paystack', 'reference' => 'PAY-TEST', 'status' => 'pending', 'amount' => 12000, 'currency' => 'NGN']);

        return [$order, $payment, $variant];
    }

    private function send(array $body, ?string $signature = null)
    {
        $raw = json_encode($body);
        $signature ??= hash_hmac('sha512', $raw, self::SECRET);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $raw);
    }

    private function event(int $kobo = 1200000): array
    {
        return ['event' => 'charge.success', 'data' => ['reference' => 'PAY-TEST', 'status' => 'success', 'amount' => $kobo, 'currency' => 'NGN', 'channel' => 'card']];
    }

    public function test_valid_signature_marks_order_paid_and_reduces_stock(): void
    {
        [$order, $payment, $variant] = $this->pendingPayment();

        $this->send($this->event())->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame(3, $variant->fresh()->stock_quantity);
    }

    public function test_replayed_webhook_is_idempotent(): void
    {
        [$order, , $variant] = $this->pendingPayment();

        $this->send($this->event())->assertOk();
        $this->send($this->event())->assertOk();

        $this->assertSame(3, $variant->fresh()->stock_quantity); // decremented once
        $this->assertSame(1, $order->statusHistories()->where('status', 'paid')->count());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        [$order] = $this->pendingPayment();

        $this->send($this->event(), 'not-a-real-signature')->assertStatus(401);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_wrong_amount_is_ignored(): void
    {
        [$order, $payment] = $this->pendingPayment();

        $this->send($this->event(100))->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
    }
}
