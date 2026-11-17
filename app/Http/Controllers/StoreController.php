<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;
use App\Support\ShareMeta;

class StoreController extends Controller
{
    public function show(): Response
    {
        $vendor = app('currentVendor');
        $products = Product::active()
            ->with(['variants', 'images'])
            ->latest()
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price_from' => $p->price_from,
                'image' => $p->primary_image_url,
                'in_stock' => $p->in_stock,
                'url' => route('store.product', ['vendor' => $vendor, 'product' => $p]),
                'quick_add_variant_id' => $p->variants->count() === 1 &&
                $p->variants->first()->stock_quantity > 0 ? $p->variants->first()->id : null,
            ]);

        return Inertia::render('Store/Show', [
            'store' => [
                'name' => $vendor->name,
                'slug' => $vendor->slug,
                'description' => $vendor->description,
                'tagline' => $vendor->tagline,
                'banner' => $vendor->banner_url,
                'logo' => $vendor->logo_url,
                'whatsapp_number' => $vendor->whatsapp_number,
                'cart_url' => route('store.cart', ['vendor' => $vendor]),
                'add_url' => route('store.cart.add', ['vendor' => $vendor]),
                'cart' => app(\App\Services\CartService::class)->panel($vendor),
            ],
            'products' => $products,
        ])->withViewData(['meta' => ShareMeta::store($vendor)]);
    }
}
