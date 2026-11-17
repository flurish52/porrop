<?php

namespace Tests\Feature;

use App\Mail\OrderLink;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderLookupTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $v = Vendor::create(['name' => 'Shop A', 'slug' => 'shop-a', 'status' => 'active']);
        $this->order = Order::create([
            'vendor_id' => $v->id, 'reference' => 'PRP-FIND0001', 'guest_email' => 'Chidi@Example.com', 'full_name' => 'Chidi',
            'fulfillment_type' => 'delivery', 'status' => 'paid', 'subtotal' => 12000, 'total_amount' => 12000,
            'commission_rate' => 10, 'commission_amount' => 1200, 'vendor_payout' => 10800,
        ]);
    }

    public function test_every_order_gets_a_long_private_token(): void
    {
        $this->assertSame(32, strlen($this->order->public_token));
    }

    public function test_matching_email_and_reference_sends_the_link(): void
    {
        Mail::fake();

        $this->post('/orders/find', ['email' => 'chidi@example.com', 'reference' => 'prp-find0001'])->assertSessionHas('success');

        Mail::assertSent(OrderLink::class, fn ($m) => $m->hasTo('Chidi@Example.com'));
    }

    public function test_wrong_details_send_nothing_but_look_identical(): void
    {
        Mail::fake();

        $this->post('/orders/find', ['email' => 'other@example.com', 'reference' => 'PRP-FIND0001'])->assertSessionHas('success');
        $this->post('/orders/find', ['email' => 'chidi@example.com', 'reference' => 'PRP-NOPE'])->assertSessionHas('success');

        Mail::assertNothingSent();
    }

    public function test_order_page_opens_with_token_only(): void
    {
        $this->get("/store/shop-a/orders/{$this->order->public_token}")->assertOk();
        $this->get('/store/shop-a/orders/PRP-FIND0001')->assertNotFound();
    }
}
