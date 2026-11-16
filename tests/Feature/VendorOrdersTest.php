<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorOrdersTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vendor = Vendor::create(['name' => 'Shop A', 'slug' => 'shop-a', 'status' => 'active']);
        $this->owner = User::factory()->create();
        $this->vendor->users()->attach($this->owner->id, ['role' => 'owner']);
    }

    private function order(Vendor $v, string $status, string $ref): Order
    {
        return Order::create([
            'vendor_id' => $v->id, 'reference' => $ref, 'guest_email' => 'c@example.com', 'full_name' => 'Chidi',
            'fulfillment_type' => 'delivery', 'status' => $status, 'subtotal' => 12000, 'total_amount' => 12000,
            'commission_rate' => 10, 'commission_amount' => 1200, 'vendor_payout' => 10800,
        ]);
    }

    public function test_list_hides_unpaid_orders_and_other_stores(): void
    {
        $other = Vendor::create(['name' => 'Shop B', 'slug' => 'shop-b', 'status' => 'active']);
        $this->order($this->vendor, 'paid', 'PRP-PAID0001');
        $this->order($this->vendor, 'pending', 'PRP-PEND0001');
        $this->order($other, 'paid', 'PRP-OTHR0001');

        $this->actingAs($this->owner)->get('/store/shop-a/dashboard/orders')
            ->assertOk()
            ->assertSee('PRP-PAID0001', false)
            ->assertDontSee('PRP-PEND0001', false)
            ->assertDontSee('PRP-OTHR0001', false);
    }

    public function test_vendor_moves_an_order_forward_and_history_is_logged(): void
    {
        $o = $this->order($this->vendor, 'paid', 'PRP-FLOW0001');
        $url = "/store/shop-a/dashboard/orders/{$o->id}/status";

        $this->actingAs($this->owner)->patch($url, ['status' => 'processing'])->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->patch($url, ['status' => 'ready'])->assertSessionHasNoErrors();

        $this->assertSame('ready', $o->fresh()->status);
        $this->assertSame(['processing', 'ready'], $o->statusHistories()->orderBy('id')->pluck('status')->all());
        $this->assertSame($this->owner->id, $o->statusHistories()->first()->changed_by);
    }

    public function test_status_cannot_go_backwards_or_be_unpaid_or_cancelled(): void
    {
        $o = $this->order($this->vendor, 'ready', 'PRP-BACK0001');
        $url = "/store/shop-a/dashboard/orders/{$o->id}/status";

        foreach (['paid', 'processing', 'cancelled', 'pending', 'nonsense'] as $bad) {
            $this->actingAs($this->owner)->patch($url, ['status' => $bad])->assertSessionHasErrors('status');
        }

        $this->assertSame('ready', $o->fresh()->status);
    }

    public function test_pending_order_cannot_be_advanced(): void
    {
        $o = $this->order($this->vendor, 'pending', 'PRP-UNPD0001');

        $this->actingAs($this->owner)->patch("/store/shop-a/dashboard/orders/{$o->id}/status", ['status' => 'completed'])
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', $o->fresh()->status);
    }

    public function test_cannot_view_or_change_another_stores_order(): void
    {
        $other = Vendor::create(['name' => 'Shop B', 'slug' => 'shop-b', 'status' => 'active']);
        $theirs = $this->order($other, 'paid', 'PRP-THEY0001');

        $this->actingAs($this->owner)->get("/store/shop-a/dashboard/orders/{$theirs->id}")->assertNotFound();
        $this->actingAs($this->owner)->patch("/store/shop-a/dashboard/orders/{$theirs->id}/status", ['status' => 'processing'])->assertNotFound();
        $this->assertSame('paid', $theirs->fresh()->status);
    }

    public function test_payout_details_validate_and_save(): void
    {
        $url = '/store/shop-a/dashboard/payout';
        $good = ['bank_name' => 'GTBank', 'account_number' => '0123456789', 'account_name' => 'Ada Obi'];

        $this->actingAs($this->owner)->put($url, array_merge($good, ['account_number' => '12345']))->assertSessionHasErrors('account_number');
        $this->actingAs($this->owner)->put($url, array_merge($good, ['account_number' => '01234abc89']))->assertSessionHasErrors('account_number');

        $this->actingAs($this->owner)->put($url, $good)->assertSessionHasNoErrors();
        $this->assertTrue($this->vendor->fresh()->hasPayoutDetails());
        $this->assertSame('0123456789', $this->vendor->fresh()->account_number);
    }

    public function test_staff_can_see_orders_but_not_payout_details(): void
    {
        $staff = User::factory()->create();
        $this->vendor->users()->attach($staff->id, ['role' => 'staff']);

        $this->actingAs($staff)->get('/store/shop-a/dashboard/orders')->assertOk();
        $this->actingAs($staff)->get('/store/shop-a/dashboard/payout')->assertForbidden();
        $this->actingAs($staff)->put('/store/shop-a/dashboard/payout', ['bank_name' => 'X', 'account_number' => '0123456789', 'account_name' => 'Y'])->assertForbidden();
    }
}
