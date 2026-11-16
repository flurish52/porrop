<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartStaysOnPageTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create(['slug' => 'shop-a', 'name' => 'Shop A', 'status' => 'active']);
        $product = Product::create(['vendor_id' => $this->vendor->id, 'name' => 'Tote', 'slug' => 'tote', 'status' => 'active']);
        $this->variant = ProductVariant::create(['vendor_id' => $this->vendor->id, 'product_id' => $product->id, 'variant_name' => 'Default', 'sku' => 'A-1', 'price' => 12000, 'stock_quantity' => 3]);
    }

    public function test_adding_goes_back_and_the_store_page_shows_the_cart(): void
    {
        $this->from('/store/shop-a')
            ->post('/store/shop-a/cart', ['variant_id' => $this->variant->id, 'quantity' => 2])
            ->assertRedirect('/store/shop-a');

        $this->get('/store/shop-a')->assertInertia(fn (Assert $page) => $page
            ->where('store.cart.count', 2)
            ->where('store.cart.subtotal', 24000)
            ->where('store.cart.quantities.'.$this->variant->id, 2)
            ->has('store.cart.lines', 1));
    }

    public function test_other_store_pages_get_the_same_cart_in_shop_prop(): void
    {
        $this->post('/store/shop-a/cart', ['variant_id' => $this->variant->id, 'quantity' => 1]);

        $this->get('/store/shop-a/cart')->assertInertia(fn (Assert $page) => $page->where('shop.cart.count', 1));
        $this->get('/store/shop-a/products/tote')->assertInertia(fn (Assert $page) => $page->where('shop.cart.count', 1));
    }

    public function test_adding_beyond_stock_gives_an_error_not_a_silent_success(): void
    {
        $this->post('/store/shop-a/cart', ['variant_id' => $this->variant->id, 'quantity' => 3]);

        $this->from('/store/shop-a')
            ->post('/store/shop-a/cart', ['variant_id' => $this->variant->id, 'quantity' => 1])
            ->assertSessionHas('error');
    }
}
