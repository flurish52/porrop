<?php

namespace Tests\Feature;

use App\Mail\NewOrderForVendor;
use App\Mail\OrderReceipt;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['paystack.secret_key' => self::SECRET]);
    }

    private function pendingPayment(): Order
    {
        $vendor = Vendor::create(['name' => 'Shop A', 'slug' => 'shop-a', 'status' => 'active', 'email' => 'shop@example.com']);
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $vendor->users()->attach($owner->id, ['role' => 'owner']);

        $order = Order::create([
            'vendor_id' => $vendor->id, 'reference' => 'PRP-MAIL0001', 'guest_email' => 'buyer@example.com', 'full_name' => 'Buyer',
            'guest_phone' => '2348031234567', 'delivery_address' => '1 Test Street, Lagos', 'fulfillment_type' => 'delivery', 'status' => 'pending',
            'subtotal' => 12000, 'total_amount' => 12000, 'commission_rate' => 10, 'commission_amount' => 1200, 'vendor_payout' => 10800,
        ]);
        $order->items()->create(['product_name' => 'Bag', 'variant_name' => 'Default', 'quantity' => 1, 'price' => 12000]);
        $order->payments()->create(['gateway' => 'paystack', 'reference' => 'PAY-MAIL', 'status' => 'pending', 'amount' => 12000, 'currency' => 'NGN']);

        return $order;
    }

    private function webhook(int $kobo = 1200000)
    {
        $raw = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'PAY-MAIL', 'status' => 'success', 'amount' => $kobo, 'currency' => 'NGN']]);

        return $this->call('POST', '/paystack/webhook', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $raw, self::SECRET),
            'CONTENT_TYPE' => 'application/json',
        ], $raw);
    }

    public function test_paying_sends_one_alert_to_the_vendor_and_one_receipt_to_the_buyer(): void
    {
        Mail::fake();
        $this->pendingPayment();

        $this->webhook()->assertOk();

        Mail::assertSent(NewOrderForVendor::class, 1);
        Mail::assertSent(NewOrderForVendor::class, fn ($m) => $m->hasTo('owner@example.com') && $m->hasTo('shop@example.com'));
        Mail::assertSent(OrderReceipt::class, 1);
        Mail::assertSent(OrderReceipt::class, fn ($m) => $m->hasTo('buyer@example.com'));
    }

    public function test_replayed_webhook_does_not_email_again(): void
    {
        Mail::fake();
        $this->pendingPayment();

        $this->webhook()->assertOk();
        $this->webhook()->assertOk();

        Mail::assertSent(NewOrderForVendor::class, 1);
        Mail::assertSent(OrderReceipt::class, 1);
    }

    public function test_wrong_amount_sends_nothing(): void
    {
        Mail::fake();
        $this->pendingPayment();

        $this->webhook(100)->assertOk();

        Mail::assertNothingSent();
    }

    public function test_broken_mail_setup_never_breaks_payment_confirmation(): void
    {
        config(['mail.default' => 'does-not-exist']);
        $order = $this->pendingPayment();

        $this->webhook()->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_emails_render(): void
    {
        $order = $this->pendingPayment()->load('items', 'vendor');

        $vendorHtml = (new NewOrderForVendor($order, $order->vendor, 'https://x.test/o'))->render();
        $this->assertStringContainsString('PRP-MAIL0001', $vendorHtml);
        $this->assertStringContainsString('1 Test Street, Lagos', $vendorHtml);
        $this->assertStringContainsString('10,800.00', $vendorHtml);

        $receiptHtml = (new OrderReceipt($order, $order->vendor, 'https://x.test/o'))->render();
        $this->assertStringContainsString('12,000.00', $receiptHtml);
        $this->assertStringNotContainsString('1,200.00', $receiptHtml); // the customer never sees Porrop's fee
    }
}
