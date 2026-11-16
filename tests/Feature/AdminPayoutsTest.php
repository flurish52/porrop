<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payout;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPayoutsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['porrop.payout_hold_days' => 3]);

        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_platform_admin' => true])->save();
    }

    private function vendor(string $slug = 'shop-a', bool $bank = true): Vendor
    {
        return Vendor::create([
            'name' => ucfirst($slug), 'slug' => $slug, 'status' => 'active',
            'bank_name' => $bank ? 'GTBank' : null, 'account_number' => $bank ? '0123456789' : null, 'account_name' => $bank ? 'Ada Obi' : null,
        ]);
    }

    private function order(Vendor $v, string $status, int $payout, ?int $paidDaysAgo = null): Order
    {
        static $n = 0;
        $order = Order::create([
            'vendor_id' => $v->id, 'reference' => 'PRP-T'.str_pad((string) ++$n, 7, '0', STR_PAD_LEFT), 'guest_email' => 'c@example.com',
            'full_name' => 'C', 'fulfillment_type' => 'delivery', 'status' => $status,
            'subtotal' => $payout + 100, 'total_amount' => $payout + 100, 'commission_rate' => 10, 'commission_amount' => 100, 'vendor_payout' => $payout,
        ]);

        if ($paidDaysAgo !== null) {
            $order->payments()->create([
                'gateway' => 'paystack', 'reference' => 'PAY-'.$n, 'status' => 'success', 'amount' => $payout + 100,
                'currency' => 'NGN', 'paid_at' => now()->subDays($paidDaysAgo),
            ]);
        }

        return $order;
    }

    public function test_only_platform_admins_can_open_it(): void
    {
        $this->get('/admin/payouts')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/payouts')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/payouts')->assertOk();
    }

    public function test_non_admin_cannot_record_a_payout(): void
    {
        $v = $this->vendor();
        $o = $this->order($v, 'completed', 1000, 5);

        $this->actingAs(User::factory()->create())->post("/admin/payouts/{$v->slug}", ['expected_amount' => 1000])->assertForbidden();
        $this->assertSame('pending', $o->fresh()->payout_status);
    }

    public function test_pays_completed_and_old_orders_but_holds_fresh_ones(): void
    {
        $v = $this->vendor();
        $done = $this->order($v, 'completed', 1000, 0);   // completed: ready now
        $old = $this->order($v, 'paid', 2000, 4);          // paid 4 days ago: past the 3-day hold
        $fresh = $this->order($v, 'paid', 5000, 1);        // paid yesterday: still on hold
        $unpaid = $this->order($v, 'pending', 9000);       // never paid: never counted

        $this->actingAs($this->admin)->post("/admin/payouts/{$v->slug}", ['expected_amount' => 3000, 'reference' => 'TRF123'])
            ->assertSessionHasNoErrors();

        $payout = Payout::firstOrFail();
        $this->assertEquals(3000, $payout->amount);
        $this->assertSame(2, $payout->order_count);
        $this->assertSame('TRF123', $payout->reference);
        $this->assertSame($this->admin->id, $payout->paid_by);

        $this->assertSame('paid', $done->fresh()->payout_status);
        $this->assertSame($payout->id, $done->fresh()->payout_id);
        $this->assertSame('paid', $old->fresh()->payout_status);
        $this->assertSame('pending', $fresh->fresh()->payout_status);
        $this->assertSame('pending', $unpaid->fresh()->payout_status);
    }

    public function test_changed_amount_is_refused_and_nothing_is_recorded(): void
    {
        $v = $this->vendor();
        $o = $this->order($v, 'completed', 1000, 0);

        $this->actingAs($this->admin)->post("/admin/payouts/{$v->slug}", ['expected_amount' => 999])->assertSessionHasErrors('payout');

        $this->assertSame(0, Payout::count());
        $this->assertSame('pending', $o->fresh()->payout_status);
    }

    public function test_same_orders_cannot_be_paid_twice(): void
    {
        $v = $this->vendor();
        $this->order($v, 'completed', 1000, 0);

        $this->actingAs($this->admin)->post("/admin/payouts/{$v->slug}", ['expected_amount' => 1000])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post("/admin/payouts/{$v->slug}", ['expected_amount' => 1000])->assertSessionHasErrors('payout');

        $this->assertSame(1, Payout::count());
    }

    public function test_vendor_without_bank_details_cannot_be_paid(): void
    {
        $v = $this->vendor('no-bank', false);
        $o = $this->order($v, 'completed', 1000, 0);

        $this->actingAs($this->admin)->post("/admin/payouts/{$v->slug}", ['expected_amount' => 1000])->assertSessionHasErrors('payout');
        $this->assertSame('pending', $o->fresh()->payout_status);
    }

    public function test_payout_only_settles_that_vendors_orders(): void
    {
        $a = $this->vendor('shop-a');
        $b = $this->vendor('shop-b');
        $this->order($a, 'completed', 1000, 0);
        $theirs = $this->order($b, 'completed', 7000, 0);

        $this->actingAs($this->admin)->post("/admin/payouts/{$a->slug}", ['expected_amount' => 1000])->assertSessionHasNoErrors();

        $this->assertSame('pending', $theirs->fresh()->payout_status);
    }

    public function test_page_lists_owed_and_held_amounts(): void
    {
        $v = $this->vendor();
        $this->order($v, 'completed', 1000, 0);
        $this->order($v, 'paid', 5000, 1);

        $this->actingAs($this->admin)->get('/admin/payouts')->assertOk()
            ->assertSee('&quot;owed&quot;:1000', false)
            ->assertSee('&quot;held&quot;:5000', false);
    }
}
