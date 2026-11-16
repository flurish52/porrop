<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'paystack.secret_key' => 'sk_test_x',
            'porrop.commission' => ['rate' => 10, 'min' => 100, 'max' => 5000],
        ]);

        [$this->vendor, $this->product, $this->variant] = $this->makeProduct('shop-a', 'Tote', 12000, 3);
    }

    private function makeProduct(string $slug, string $name, int $price, int $stock, string $status = 'active'): array
    {
        $vendor = Vendor::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug), 'status' => 'active']);
        $product = Product::create(['vendor_id' => $vendor->id, 'name' => $name, 'slug' => str($name)->slug(), 'status' => $status]);
        $variant = ProductVariant::create(['vendor_id' => $vendor->id, 'product_id' => $product->id, 'variant_name' => 'Default', 'sku' => strtoupper($slug).'-1', 'price' => $price, 'stock_quantity' => $stock]);

        return [$vendor, $product, $variant];
    }

    private function fakePaystack(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/abc', 'access_code' => 'abc', 'reference' => 'x'],
        ])]);
    }

    private function details(array $over = []): array
    {
        return array_merge([
            'full_name' => 'Chidi Okafor',
            'email' => 'chidi@example.com',
            'phone' => '0803 123 4567',
            'delivery_address' => '12 Allen Avenue, Ikeja, Lagos',
        ], $over);
    }

    public function test_product_page_shows_and_draft_is_404(): void
    {
        $this->get('/store/shop-a/products/tote')->assertOk()->assertSee('Tote', false);

        $this->makeProduct('shop-a', 'Secret', 500, 1, 'draft');
        $this->get('/store/shop-a/products/secret')->assertNotFound();
    }

    public function test_product_of_another_store_is_404_under_this_store(): void
    {
        $this->makeProduct('shop-b', 'Other', 900, 1);

        $this->get('/store/shop-a/products/other')->assertNotFound();
    }

    public function test_add_to_cart_is_capped_at_stock(): void
    {
        $this->post('/store/shop-a/cart', ['variant_id' => $this->variant->id, 'quantity' => 10])
            ->assertRedirect();

        $this->assertSame([$this->variant->id => 3], session('cart.'.$this->vendor->id));
    }

    public function test_cannot_add_a_variant_from_another_store(): void
    {
        [, , $foreign] = $this->makeProduct('shop-b', 'Other', 900, 5);

        $this->post('/store/shop-a/cart', ['variant_id' => $foreign->id, 'quantity' => 1])->assertNotFound();
        $this->assertNull(session('cart.'.$this->vendor->id));
    }

    public function test_cart_update_and_remove(): void
    {
        $key = 'cart.'.$this->vendor->id;
        $this->withSession([$key => [$this->variant->id => 1]]);

        $this->patch("/store/shop-a/cart/{$this->variant->id}", ['quantity' => 2])->assertRedirect();
        $this->assertSame([$this->variant->id => 2], session($key));

        $this->delete("/store/shop-a/cart/{$this->variant->id}")->assertRedirect();
        $this->assertSame([], session($key));
    }

    public function test_cart_drops_items_that_sold_out_or_were_hidden(): void
    {
        $key = 'cart.'.$this->vendor->id;
        $this->variant->update(['stock_quantity' => 0]);

        $this->withSession([$key => [$this->variant->id => 2]])
            ->get('/store/shop-a/cart')->assertOk();

        $this->assertSame([], session($key));
    }

    public function test_empty_cart_cannot_check_out(): void
    {
        $this->get('/store/shop-a/checkout')->assertRedirect('/store/shop-a/cart');
        $this->post('/store/shop-a/checkout', $this->details())->assertSessionHasErrors('cart');
    }

    public function test_checkout_creates_pending_order_with_commission_and_redirects_to_paystack(): void
    {
        $this->fakePaystack();
        $this->withSession(['cart.'.$this->vendor->id => [$this->variant->id => 2]]);

        $this->post('/store/shop-a/checkout', $this->details())->assertRedirect('https://checkout.paystack.com/abc');

        $order = Order::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame($this->vendor->id, $order->vendor_id);
        $this->assertEquals(24000, $order->total_amount);
        $this->assertEquals(2400, $order->commission_amount);
        $this->assertEquals(21600, $order->vendor_payout);
        $this->assertSame('2348031234567', $order->guest_phone);
        $this->assertSame('Tote', $order->items()->first()->product_name);

        // Paystack was asked for the exact amount in kobo
        Http::assertSent(fn ($r) => $r['amount'] === 2400000 && $r['currency'] === 'NGN');
    }

    public function test_checkout_validates_contact_details(): void
    {
        $this->withSession(['cart.'.$this->vendor->id => [$this->variant->id => 1]]);

        $this->post('/store/shop-a/checkout', $this->details(['phone' => '123', 'delivery_address' => 'x']))
            ->assertSessionHasErrors(['phone', 'delivery_address']);
        $this->assertSame(0, Order::withoutGlobalScopes()->count());
    }

    public function test_paystack_failure_keeps_the_cart_and_shows_an_error(): void
    {
        Http::fake(['api.paystack.co/*' => Http::response(['status' => false, 'message' => 'bad'], 400)]);
        $key = 'cart.'.$this->vendor->id;
        $this->withSession([$key => [$this->variant->id => 1]]);

        $this->post('/store/shop-a/checkout', $this->details())->assertSessionHasErrors('payment');
        $this->assertSame([$this->variant->id => 1], session($key));
    }

    public function test_order_page_is_scoped_to_its_store(): void
    {
        $order = Order::create([
            'vendor_id' => $this->vendor->id, 'reference' => 'PRP-ABCD1234', 'guest_email' => 'c@example.com',
            'full_name' => 'Chidi', 'fulfillment_type' => 'delivery', 'status' => 'paid',
            'subtotal' => 100, 'total_amount' => 100, 'commission_rate' => 10, 'commission_amount' => 100, 'vendor_payout' => 0,
        ]);

        $this->get('/store/shop-a/orders/PRP-ABCD1234')->assertOk()->assertSee('PRP-ABCD1234', false);

        $this->makeProduct('shop-b', 'Other', 900, 1);
        $this->get('/store/shop-b/orders/PRP-ABCD1234')->assertNotFound();
    }

    public function test_order_page_never_exposes_commission_to_customers(): void
    {
        Order::create([
            'vendor_id' => $this->vendor->id, 'reference' => 'PRP-NOFEE001', 'guest_email' => 'c@example.com',
            'full_name' => 'Chidi', 'fulfillment_type' => 'delivery', 'status' => 'paid',
            'subtotal' => 12000, 'total_amount' => 12000, 'commission_rate' => 10, 'commission_amount' => 1200, 'vendor_payout' => 10800,
        ]);

        $this->get('/store/shop-a/orders/PRP-NOFEE001')->assertOk()
            ->assertDontSee('commission', false)
            ->assertDontSee('vendor_payout', false);
    }
}
