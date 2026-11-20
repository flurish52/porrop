<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_non_admins_are_blocked(): void
    {
        $this->get('/admin/stores')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/stores')->assertForbidden();
    }

    public function test_admin_sees_stores_products_and_activity(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $v = Vendor::create(['name' => 'Ada Thrift', 'slug' => 'ada', 'status' => 'active']);
        $owner = User::factory()->create();
        $v->users()->attach($owner->id, ['role' => 'owner']);
        Product::create(['vendor_id' => $v->id, 'name' => 'Leather tote', 'slug' => 'leather-tote', 'status' => 'active']);

        $this->actingAs($admin)->get('/admin/stores')->assertOk()
            ->assertSee('Ada Thrift', false)->assertSee('Leather tote', false);

        $this->actingAs($admin)->get('/admin/stores/ada')->assertOk()
            ->assertSee('Leather tote', false)->assertSee($owner->email, false);
    }
}
