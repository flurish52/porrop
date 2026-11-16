<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class ProductController extends VendorAreaController
{
    public function __construct(private ProductService $products) {}

    public function index(): Response
    {
        $vendor = app('currentVendor');

        // Product::query() is already limited to this store by the BelongsToVendor scope.
        $products = Product::with(['variants', 'images'])
            ->latest()
            ->paginate(20)
            ->through(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
                'image' => $p->primary_image_url,
                'price_from' => $p->price_from,
                'variant_count' => $p->variants->count(),
                'stock' => (int) $p->variants->sum('stock_quantity'),
                'editUrl' => route('vendor.products.edit', ['vendor' => $vendor, 'product' => $p]),
            ]);

        return $this->page('Vendor/Products/Index', [
            'products' => $products,
            'createUrl' => route('vendor.products.create', ['vendor' => $vendor]),
        ]);
    }

    public function create(): Response
    {
        $vendor = app('currentVendor');

        return $this->page('Vendor/Products/Create', [
            'storeUrl' => route('vendor.products.store', ['vendor' => $vendor]),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $vendor = app('currentVendor');

        $product = $this->products->create($vendor, $request->validated());

        // Images need a saved product, so send them straight to the edit page where the uploader is.
        return redirect()
            ->route('vendor.products.edit', ['vendor' => $vendor, 'product' => $product])
            ->with('success', 'Product saved. Now add some photos.');
    }

    public function edit(Product $product): Response
    {
        $vendor = app('currentVendor');
        $product->load(['variants', 'images']);

        return $this->page('Vendor/Products/Edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'status' => $product->status,
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'variant_name' => $v->variant_name === 'Default' && $product->variants->count() === 1 ? '' : $v->variant_name,
                    'price' => (float) $v->price,
                    'stock_quantity' => $v->stock_quantity,
                ])->values(),
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->url,
                    'is_primary' => $img->is_primary,
                    'variant_id' => $img->product_variant_id,
                    'updateUrl' => route('vendor.products.images.update', ['vendor' => $vendor, 'product' => $product, 'image' => $img]),
                    'deleteUrl' => route('vendor.products.images.destroy', ['vendor' => $vendor, 'product' => $product, 'image' => $img]),
                ])->values(),
            ],
            'updateUrl' => route('vendor.products.update', ['vendor' => $vendor, 'product' => $product]),
            'deleteUrl' => route('vendor.products.destroy', ['vendor' => $vendor, 'product' => $product]),
            'uploadUrl' => route('vendor.products.images.store', ['vendor' => $vendor, 'product' => $product]),
            'maxImages' => config('porrop.max_images_per_product'),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update(app('currentVendor'), $product, $request->validated());

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Soft delete: it disappears from the store, past orders keep their snapshots.
        $product->delete();

        return redirect()
            ->route('vendor.products.index', ['vendor' => app('currentVendor')])
            ->with('success', 'Product deleted.');
    }
}
