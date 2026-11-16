<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge([
            'store_name' => "Ada's Thrift",
            'slug' => 'adas-thrift',
            'whatsapp_number' => '0803 123 4567',
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'password123',
        ], $over);
    }

    public function test_guest_registers_and_lands_on_dashboard(): void
    {
        config(['porrop.auto_approve' => true]);

        $this->post('/vendor/register', $this->payload())
            ->assertRedirect('/store/adas-thrift/dashboard');

        $vendor = Vendor::where('slug', 'adas-thrift')->firstOrFail();
        $this->assertSame('234803'.'1234567', $vendor->whatsapp_number);
        $this->assertSame('active', $vendor->status);
        $this->assertAuthenticated();
        $this->assertSame('owner', User::first()->vendors()->first()->pivot->role);
    }

    public function test_pending_when_auto_approve_is_off(): void
    {
        config(['porrop.auto_approve' => false]);
        $this->post('/vendor/register', $this->payload());
        $this->assertSame('pending', Vendor::first()->status);
    }

    public function test_duplicate_and_reserved_slugs_are_rejected(): void
    {
        Vendor::create(['name' => 'X', 'slug' => 'taken', 'status' => 'active']);

        $this->post('/vendor/register', $this->payload(['slug' => 'taken']))->assertSessionHasErrors('slug');
        $this->post('/vendor/register', $this->payload(['slug' => 'admin', 'email' => 'b@example.com']))->assertSessionHasErrors('slug');
        $this->post('/vendor/register', $this->payload(['slug' => 'Bad Slug!', 'email' => 'c@example.com']))->assertSessionHasErrors('slug');
    }

    public function test_slug_check_endpoint(): void
    {
        Vendor::create(['name' => 'X', 'slug' => 'taken', 'status' => 'active']);

        $this->getJson('/vendor/slug-check?slug=taken')->assertJson(['available' => false]);
        $this->getJson('/vendor/slug-check?slug=fresh-name')->assertJson(['available' => true]);
        $this->getJson('/vendor/slug-check?slug=a')->assertJson(['available' => false]);
    }
}
