<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProfileTest extends TestCase
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

    public function test_owner_updates_the_public_profile(): void
    {
        $this->actingAs($this->owner)->put('/store/shop-a/dashboard/profile', [
            'name' => 'Shop Alpha',
            'tagline' => 'Handmade bags',
            'description' => 'We make bags.',
            'whatsapp_number' => '0803 123 4567',
            'listed_in_directory' => true,
        ])->assertSessionHasNoErrors();

        $v = $this->vendor->fresh();
        $this->assertSame('Shop Alpha', $v->name);
        $this->assertSame('Handmade bags', $v->tagline);
        $this->assertTrue($v->listed_in_directory);
    }

    public function test_name_is_required_and_tagline_is_limited(): void
    {
        $this->actingAs($this->owner)->put('/store/shop-a/dashboard/profile', ['name' => '', 'tagline' => str_repeat('a', 101)])
            ->assertSessionHasErrors(['name', 'tagline']);
    }

    public function test_staff_cannot_open_the_profile_page(): void
    {
        $staff = User::factory()->create();
        $this->vendor->users()->attach($staff->id, ['role' => 'staff']);

        $this->actingAs($staff)->get('/store/shop-a/dashboard/profile')->assertForbidden();
    }
}
