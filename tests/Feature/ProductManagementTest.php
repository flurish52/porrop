<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'porrop.cloudinary_cloud' => 'democloud',
            'porrop.cloudinary_key' => 'key123',
            'porrop.cloudinary_secret' => 'secret456',
        ]);

        $this->vendor = Vendor::create(['name' => 'Shop A', 'slug' => 'shop-a', 'status' => 'active']);
        $this->owner = User::factory()->create();
        $this->vendor->users()->attach($this->owner->id, ['role' => 'owner']);
    }

    private function url(string $path = ''): string
    {
        return '/store/shop-a/dashboard/products'.$path;
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'name' => 'Leather tote',
            'description' => 'Nice bag',
            'status' => 'active',
            'variants' => [['variant_name' => '', 'price' => 12000, 'stock_quantity' => 5]],
        ], $over);
    }

    public function test_simple_product_gets_one_default_variant(): void
    {
        $this->actingAs($this->owner)->post($this->url(), $this->payload())->assertRedirect();

        $product = Product::firstOrFail();
        $this->assertSame($this->vendor->id, $product->vendor_id);
        $this->assertSame('leather-tote', $product->slug);
        $this->assertCount(1, $product->variants);
        $this->assertSame('Default', $product->variants->first()->variant_name);
        $this->assertNotEmpty($product->variants->first()->sku);
    }

    public function test_multiple_variants_need_names(): void
    {
        $this->actingAs($this->owner)->post($this->url(), $this->payload([
            'variants' => [
                ['variant_name' => 'Small', 'price' => 5000, 'stock_quantity' => 2],
                ['variant_name' => '', 'price' => 6000, 'stock_quantity' => 2],
            ],
        ]))->assertSessionHasErrors('variants.1.variant_name');
    }

    public function test_duplicate_names_get_unique_slugs(): void
    {
        $this->actingAs($this->owner)->post($this->url(), $this->payload());
        $this->actingAs($this->owner)->post($this->url(), $this->payload());

        $this->assertEqualsCanonicalizing(['leather-tote', 'leather-tote-2'], Product::pluck('slug')->all());
    }

    public function test_update_edits_adds_and_removes_variants_and_keeps_slug(): void
    {
        $this->actingAs($this->owner)->post($this->url(), $this->payload([
            'variants' => [
                ['variant_name' => 'Small', 'price' => 5000, 'stock_quantity' => 2],
                ['variant_name' => 'Large', 'price' => 6000, 'stock_quantity' => 3],
            ],
        ]));
        $product = Product::firstOrFail();
        [$small, $large] = $product->variants->all();

        $this->actingAs($this->owner)->put($this->url("/{$product->id}"), $this->payload([
            'name' => 'Renamed tote',
            'variants' => [
                ['id' => $small->id, 'variant_name' => 'Small', 'price' => 5500, 'stock_quantity' => 9],
                ['variant_name' => 'XL', 'price' => 7000, 'stock_quantity' => 1],
            ],
        ]))->assertRedirect();

        $product->refresh();
        $this->assertSame('leather-tote', $product->slug);
        $this->assertSame('Renamed tote', $product->name);
        $this->assertSame(['Small', 'XL'], $product->variants()->orderBy('id')->pluck('variant_name')->all());
        $this->assertSoftDeleted('product_variants', ['id' => $large->id]);
        $this->assertEquals(5500, $small->fresh()->price);
    }

    public function test_cannot_touch_another_vendors_product(): void
    {
        $other = Vendor::create(['name' => 'Shop B', 'slug' => 'shop-b', 'status' => 'active']);
        $theirs = Product::create(['vendor_id' => $other->id, 'name' => 'Theirs', 'slug' => 'theirs', 'status' => 'active']);

        $this->actingAs($this->owner)->get($this->url("/{$theirs->id}/edit"))->assertNotFound();
        $this->actingAs($this->owner)->put($this->url("/{$theirs->id}"), $this->payload())->assertNotFound();
        $this->actingAs($this->owner)->delete($this->url("/{$theirs->id}"))->assertNotFound();
        $this->assertNotSoftDeleted($theirs);
    }

    public function test_variant_id_from_another_product_is_rejected(): void
    {
        $this->actingAs($this->owner)->post($this->url(), $this->payload(['name' => 'One']));
        $this->actingAs($this->owner)->post($this->url(), $this->payload(['name' => 'Two']));
        [$one, $two] = Product::orderBy('id')->get()->all();
        $foreignVariantId = $two->variants()->first()->id;

        $this->actingAs($this->owner)->put($this->url("/{$one->id}"), $this->payload([
            'variants' => [['id' => $foreignVariantId, 'variant_name' => '', 'price' => 1, 'stock_quantity' => 1]],
        ]))->assertSessionHasErrors('variants.0.id');

        $this->assertEquals(12000, $two->variants()->first()->price); // untouched
    }

    public function test_destroy_soft_deletes(): void
    {
        $this->actingAs($this->owner)->post($this->url(), $this->payload());
        $product = Product::firstOrFail();

        $this->actingAs($this->owner)->delete($this->url("/{$product->id}"))->assertRedirect();
        $this->assertSoftDeleted($product);
    }

    private function productWithVariants(): Product
    {
        $this->actingAs($this->owner)->post($this->url(), $this->payload([
            'variants' => [
                ['variant_name' => 'Red', 'price' => 5000, 'stock_quantity' => 2],
                ['variant_name' => 'Blue', 'price' => 5000, 'stock_quantity' => 2],
            ],
        ]));

        return Product::firstOrFail();
    }

    public function test_upload_stores_public_ids_and_first_becomes_primary(): void
    {
        Http::fake(['api.cloudinary.com/*' => Http::sequence()
            ->push(['public_id' => 'porrop/vendor-1/products/a'])
            ->push(['public_id' => 'porrop/vendor-1/products/b'])]);

        $product = $this->productWithVariants();

        $this->actingAs($this->owner)->post($this->url("/{$product->id}/images"), [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ])->assertSessionHasNoErrors();

        $images = $product->images()->orderBy('id')->get();
        $this->assertCount(2, $images);
        $this->assertTrue($images[0]->is_primary);
        $this->assertFalse($images[1]->is_primary);
        $this->assertNull($images[0]->product_variant_id);
        $this->assertSame('porrop/vendor-1/products/a', $images[0]->cloudinary_public_id);
    }

    public function test_image_can_be_linked_to_own_variant_but_not_a_foreign_one(): void
    {
        Http::fake(['api.cloudinary.com/*' => Http::response(['public_id' => 'x/y'])]);
        $product = $this->productWithVariants();
        $red = $product->variants()->where('variant_name', 'Red')->first();

        $this->actingAs($this->owner)->post($this->url("/{$product->id}/images"), [
            'images' => [UploadedFile::fake()->image('a.jpg')],
            'product_variant_id' => $red->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame($red->id, $product->images()->first()->product_variant_id);

        $this->actingAs($this->owner)->post($this->url("/{$product->id}/images"), [
            'images' => [UploadedFile::fake()->image('b.jpg')],
            'product_variant_id' => 999999,
        ])->assertSessionHasErrors('product_variant_id');
        $this->assertCount(1, $product->images);
    }

    public function test_non_images_and_oversize_are_rejected(): void
    {
        Http::fake();
        $product = $this->productWithVariants();

        $this->actingAs($this->owner)->post($this->url("/{$product->id}/images"), [
            'images' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('images.0');

        $this->actingAs($this->owner)->post($this->url("/{$product->id}/images"), [
            'images' => [UploadedFile::fake()->image('big.jpg')->size(6000)],
        ])->assertSessionHasErrors('images.0');

        Http::assertNothingSent();
    }

    public function test_image_limit(): void
    {
        Http::fake(['api.cloudinary.com/*' => Http::response(['public_id' => 'x/y'])]);
        config(['porrop.max_images_per_product' => 2]);
        $product = $this->productWithVariants();

        $this->actingAs($this->owner)->post($this->url("/{$product->id}/images"), [
            'images' => [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg')],
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->post($this->url("/{$product->id}/images"), [
            'images' => [UploadedFile::fake()->image('3.jpg')],
        ])->assertSessionHasErrors('images');
    }

    public function test_cloudinary_failure_shows_error_and_saves_nothing(): void
    {
        Http::fake(['api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Invalid key']], 401)]);
        $product = $this->productWithVariants();

        $this->actingAs($this->owner)->post($this->url("/{$product->id}/images"), [
            'images' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertSessionHasErrors('images');

        $this->assertCount(0, $product->images);
    }

    public function test_make_primary_and_delete_promotes_next(): void
    {
        Http::fake(['api.cloudinary.com/*' => Http::response(['public_id' => 'x/y'])]);
        $product = $this->productWithVariants();
        $a = $product->images()->create(['cloudinary_public_id' => 'a', 'is_primary' => true]);
        $b = $product->images()->create(['cloudinary_public_id' => 'b', 'is_primary' => false]);

        $this->actingAs($this->owner)->patch($this->url("/{$product->id}/images/{$b->id}"), ['is_primary' => true]);
        $this->assertTrue($b->fresh()->is_primary);
        $this->assertFalse($a->fresh()->is_primary);

        $this->actingAs($this->owner)->delete($this->url("/{$product->id}/images/{$b->id}"));
        $this->assertSoftDeleted($b);
        $this->assertTrue($a->fresh()->is_primary);
    }

    public function test_cannot_touch_images_of_another_product(): void
    {
        Http::fake();
        $product = $this->productWithVariants();
        $other = Vendor::create(['name' => 'Shop B', 'slug' => 'shop-b', 'status' => 'active']);
        $theirProduct = Product::create(['vendor_id' => $other->id, 'name' => 'Theirs', 'slug' => 'theirs', 'status' => 'active']);
        $theirImage = $theirProduct->images()->create(['cloudinary_public_id' => 'z', 'is_primary' => true]);

        // Image belongs to a different product than the one in the URL -> 404 through scoped bindings.
        $this->actingAs($this->owner)->delete($this->url("/{$product->id}/images/{$theirImage->id}"))->assertNotFound();
        $this->assertNotSoftDeleted($theirImage);
    }

    public function test_staff_of_another_store_is_forbidden(): void
    {
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get($this->url())->assertForbidden();
    }
}
