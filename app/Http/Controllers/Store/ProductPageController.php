<?php

namespace App\Http\Controllers\Store;

use App\Models\Product;
use Inertia\Response;

class ProductPageController extends StoreAreaController
{
    public function show(Product $product): Response
    {
        $vendor = app('currentVendor');

        // Route binding finds drafts too. Customers must never see them.
        abort_unless($product->status === Product::STATUS_ACTIVE, 404);

        $product->load(['variants', 'images']);

        return $this->page('Store/Product', [
            'product' => [
                'name' => $product->name,
                'description' => $product->description,
                'images' => $product->images->map(fn ($i) => [
                    'url' => $i->url,
                    'variant_id' => $i->product_variant_id,
                    'is_primary' => $i->is_primary,
                ])->values(),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->variant_name === 'Default' && $product->variants->count() === 1 ? null : $v->variant_name,
                    'price' => (float) $v->price,
                    'stock' => (int) $v->stock_quantity,
                ])->values(),
            ],
            'addUrl' => route('store.cart.add', ['vendor' => $vendor]),
        ]);
    }
}
