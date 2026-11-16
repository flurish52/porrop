<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $slug, string $status = 'active'): Vendor
    {
        return Vendor::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => $status]);
    }

    private function product(Vendor $v, string $name): Product
    {
        return Product::create(['vendor_id' => $v->id, 'name' => $name, 'slug' => str($name)->slug(), 'status' => 'active']);
    }

    public function test_storefront_shows_only_its_own_products(): void
    {
        $a = $this->vendor('shop-a');
        $b = $this->vendor('shop-b');
        $this->product($a, 'Alpha Bag');
        $this->product($b, 'Bravo Shoe');

        $this->get('/store/shop-a')
            ->assertOk()
            ->assertSee('Alpha Bag', false)
            ->assertDontSee('Bravo Shoe', false);
    }

    public function test_inactive_store_is_404_for_public(): void
    {
        $this->vendor('hidden', 'pending');
        $this->get('/store/hidden')->assertNotFound();
    }

    public function test_tenant_does_not_leak_between_requests(): void
    {
        $this->vendor('shop-a');
        $this->get('/store/shop-a')->assertOk();
        $this->assertFalse(app()->bound('currentVendor'));
    }

    public function test_dashboard_requires_membership(): void
    {
        $a = $this->vendor('shop-a');
        $b = $this->vendor('shop-b');
        $owner = User::factory()->create();
        $a->users()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($owner)->get('/store/shop-a/dashboard')->assertOk();
        $this->actingAs($owner)->get('/store/shop-b/dashboard')->assertForbidden();
        $this->get('/store/shop-a/dashboard')->assertRedirect('/login');
    }
}
