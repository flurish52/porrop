<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoresDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function store(string $slug, array $over = [], string $productStatus = 'active'): Vendor
    {
        $v = Vendor::create(array_merge(['name' => ucfirst($slug), 'slug' => $slug, 'status' => 'active', 'listed_in_directory' => true], $over));
        $p = Product::create(['vendor_id' => $v->id, 'name' => 'Item '.$slug, 'slug' => 'item-'.$slug, 'status' => $productStatus]);
        ProductVariant::create(['vendor_id' => $v->id, 'product_id' => $p->id, 'variant_name' => 'Default', 'sku' => strtoupper($slug), 'price' => 5000, 'stock_quantity' => 2]);

        return $v;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['porrop.payout_mode' => 'manual']);
    }

    public function test_directory_lists_only_eligible_stores(): void
    {
        $this->store('visible');
        $this->store('hidden-by-owner', ['listed_in_directory' => false]);
        $this->store('suspended', ['status' => 'suspended']);
        $this->store('no-live-products', [], 'draft');

        $this->get('/stores')->assertOk()
            ->assertSee('Visible', false)
            ->assertDontSee('Hidden-by-owner', false)
            ->assertDontSee('Suspended', false)
            ->assertDontSee('No-live-products', false);
    }

    public function test_in_split_mode_stores_without_payout_details_are_not_listed(): void
    {
        config(['porrop.payout_mode' => 'split']);
        $this->store('unpaid');
        $this->store('ready', ['paystack_subaccount_code' => 'ACCT_x']);

        $this->get('/stores')->assertOk()->assertSee('Ready', false)->assertDontSee('Unpaid', false);
    }

    public function test_search_filters_by_name(): void
    {
        $this->store('lagos-bags');
        $this->store('abuja-shoes');

        $this->get('/stores?q=bags')->assertOk()->assertSee('Lagos-bags', false)->assertDontSee('Abuja-shoes', false);
    }
}
