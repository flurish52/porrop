<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryException;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductImageController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function store(Request $request, Product $product): RedirectResponse
    {
        $vendor = app('currentVendor');
        $max = (int) config('porrop.max_images_per_product');

        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:'.$max],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 MB each
            'product_variant_id' => ['nullable', 'integer'],
        ], [
            'images.*.image' => 'Each file must be a photo.',
            'images.*.mimes' => 'Use JPG, PNG or WebP photos.',
            'images.*.max' => 'Each photo must be under 5 MB.',
        ]);

        $variantId = $this->variantIdOrFail($product, $data['product_variant_id'] ?? null);

        if ($product->images()->count() + count($data['images']) > $max) {
            throw ValidationException::withMessages(['images' => "A product can have up to {$max} photos."]);
        }

        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $failed = null;

        foreach ($data['images'] as $file) {
            try {
                $publicId = $this->cloudinary->upload($file, "porrop/vendor-{$vendor->id}/products");
            } catch (CloudinaryException $e) {
                $failed = $e->getMessage();
                break; // keep what already uploaded, report the problem
            }

            $product->images()->create([
                'product_variant_id' => $variantId,
                'cloudinary_public_id' => $publicId,
                'is_primary' => ! $hasPrimary,
            ]);
            $hasPrimary = true;
        }

        if ($failed) {
            return back()->withErrors(['images' => $failed]);
        }

        return back()->with('success', 'Photos added.');
    }

    /** Make main, and/or attach to one variant (null = belongs to the whole product). */
    public function update(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        $data = $request->validate([
            'product_variant_id' => ['sometimes', 'nullable', 'integer'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('product_variant_id', $data)) {
            $image->update(['product_variant_id' => $this->variantIdOrFail($product, $data['product_variant_id'])]);
        }

        if (! empty($data['is_primary'])) {
            $product->images()->where('id', '!=', $image->id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        }

        return back();
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        $wasPrimary = $image->is_primary;
        $publicId = $image->cloudinary_public_id;

        $image->delete();
        $this->cloudinary->destroy($publicId);

        if ($wasPrimary) {
            $product->images()->oldest('id')->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Photo removed.');
    }

    /** A variant id from the request must belong to this product (never trust ids from the browser). */
    private function variantIdOrFail(Product $product, mixed $variantId): ?int
    {
        if ($variantId === null || $variantId === '') {
            return null;
        }

        if (! $product->variants()->whereKey($variantId)->exists()) {
            throw ValidationException::withMessages(['product_variant_id' => 'Pick one of this product\'s options.']);
        }

        return (int) $variantId;
    }
}
