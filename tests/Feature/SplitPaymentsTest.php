<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SplitPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'paystack.secret_key' => 'sk_test_x',
            'porrop.payout_mode' => 'split',
            'porrop.verify_bank_accounts' => true,
            'porrop.commission' => ['rate' => 10, 'min' => 100, 'max' => 5000],
        ]);
        Cache::flush();

        $this->vendor = Vendor::create(['name' => 'Shop A', 'slug' => 'shop-a', 'status' => 'active']);
        $product = Product::create(['vendor_id' => $this->vendor->id, 'name' => 'Tote', 'slug' => 'tote', 'status' => 'active']);
        $this->variant = ProductVariant::create(['vendor_id' => $this->vendor->id, 'product_id' => $product->id, 'variant_name' => 'Default', 'sku' => 'A-1', 'price' => 12000, 'stock_quantity' => 5]);
    }

    private function customer(): array
    {
        return ['full_name' => 'Chidi', 'email' => 'c@example.com', 'phone' => '0803', 'fulfillment_type' => 'delivery', 'delivery_address' => '12 Allen Ave'];
    }

    private function fakeInitialize(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/abc', 'access_code' => 'abc', 'reference' => 'x'],
        ])]);
    }

    public function test_checkout_is_blocked_until_the_store_has_a_subaccount(): void
    {
        $this->fakeInitialize();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app()->instance('currentVendor', $this->vendor);
        app(OrderService::class)->checkout($this->vendor, $this->customer(), [['variant_id' => $this->variant->id, 'quantity' => 1]]);
    }

    public function test_payment_is_started_with_the_subaccount_and_the_flat_commission_in_kobo(): void
    {
        $this->vendor->update(['paystack_subaccount_code' => 'ACCT_abc123']);
        $this->fakeInitialize();
        app()->instance('currentVendor', $this->vendor);

        // 12,000 x 1 -> 10% = 1,200 commission
        app(OrderService::class)->checkout($this->vendor, $this->customer(), [['variant_id' => $this->variant->id, 'quantity' => 1]]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/transaction/initialize')
                && $request['subaccount'] === 'ACCT_abc123'
                && $request['transaction_charge'] === 120000
                && $request['amount'] === 1200000
                && $request['bearer'] === 'account';
        });
    }

    public function test_the_5000_cap_is_what_is_sent_for_a_big_order(): void
    {
        $this->vendor->update(['paystack_subaccount_code' => 'ACCT_abc123']);
        $this->variant->update(['price' => 100000, 'stock_quantity' => 10]);
        $this->fakeInitialize();
        app()->instance('currentVendor', $this->vendor);

        app(OrderService::class)->checkout($this->vendor, $this->customer(), [['variant_id' => $this->variant->id, 'quantity' => 1]]); // 10% would be 10,000

        Http::assertSent(fn ($r) => str_contains($r->url(), '/transaction/initialize') && $r['transaction_charge'] === 500000);
    }

    public function test_a_paid_split_order_is_marked_settled_by_paystack(): void
    {
        $this->vendor->update(['paystack_subaccount_code' => 'ACCT_abc123']);
        $this->fakeInitialize();
        app()->instance('currentVendor', $this->vendor);

        $result = app(OrderService::class)->checkout($this->vendor, $this->customer(), [['variant_id' => $this->variant->id, 'quantity' => 1]]);
        $payment = $result['payment'];

        app(OrderService::class)->markPaid($payment, ['status' => 'success', 'amount' => $payment->amountInKobo(), 'currency' => 'NGN']);

        $this->assertSame(Order::STATUS_PAID, $result['order']->fresh()->status);
        $this->assertSame(Order::PAYOUT_PAID, $result['order']->fresh()->payout_status);
    }

    public function test_owner_saving_bank_details_creates_a_subaccount_with_the_verified_name(): void
    {
        $owner = User::factory()->create();
        $this->vendor->users()->attach($owner->id, ['role' => 'owner']);

        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response(['status' => true, 'data' => ['account_name' => 'ADA OBI']]),
            'api.paystack.co/bank*' => Http::response(['status' => true, 'data' => [['name' => 'Guaranty Trust Bank', 'code' => '058', 'active' => true]]]),
            'api.paystack.co/subaccount' => Http::response(['status' => true, 'data' => ['subaccount_code' => 'ACCT_new1']]),
        ]);

        $this->actingAs($owner)->put('/store/shop-a/dashboard/payout', ['bank_code' => '058', 'account_number' => '0123456789', 'confirmed' => true])
            ->assertSessionHasNoErrors();

        $v = $this->vendor->fresh();
        $this->assertSame('ACCT_new1', $v->paystack_subaccount_code);
        $this->assertSame('ADA OBI', $v->account_name);
        $this->assertSame('Guaranty Trust Bank', $v->bank_name);
        $this->assertSame('058', $v->bank_code);
    }

    public function test_a_wrong_account_number_is_rejected_and_nothing_is_saved(): void
    {
        $owner = User::factory()->create();
        $this->vendor->users()->attach($owner->id, ['role' => 'owner']);

        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response(['status' => false, 'message' => 'Could not resolve account name.'], 422),
            'api.paystack.co/bank*' => Http::response(['status' => true, 'data' => [['name' => 'Guaranty Trust Bank', 'code' => '058', 'active' => true]]]),
        ]);

        $this->actingAs($owner)->put('/store/shop-a/dashboard/payout', ['bank_code' => '058', 'account_number' => '0000000000', 'confirmed' => true])
            ->assertSessionHasErrors('account_number');

        $this->assertNull($this->vendor->fresh()->paystack_subaccount_code);
    }

    public function test_saving_without_confirming_the_name_is_refused(): void
    {
        $owner = User::factory()->create();
        $this->vendor->users()->attach($owner->id, ['role' => 'owner']);
        Http::fake();

        $this->actingAs($owner)->put('/store/shop-a/dashboard/payout', ['bank_code' => '058', 'account_number' => '0123456789'])
            ->assertSessionHasErrors('confirmed');

        $this->assertNull($this->vendor->fresh()->paystack_subaccount_code);
    }

    public function test_check_returns_the_banks_name_for_the_account_without_saving(): void
    {
        $owner = User::factory()->create();
        $this->vendor->users()->attach($owner->id, ['role' => 'owner']);

        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response(['status' => true, 'data' => ['account_name' => 'ADA OBI']]),
            'api.paystack.co/bank*' => Http::response(['status' => true, 'data' => [['name' => 'Guaranty Trust Bank', 'code' => '058', 'active' => true]]]),
        ]);

        $this->actingAs($owner)->getJson('/store/shop-a/dashboard/payout/check?bank_code=058&account_number=0123456789')
            ->assertOk()
            ->assertJson(['account_name' => 'ADA OBI', 'bank_name' => 'Guaranty Trust Bank']);

        $this->assertNull($this->vendor->fresh()->paystack_subaccount_code);
    }
}
