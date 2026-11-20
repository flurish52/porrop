<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Str;

/**
 * Link-preview tags (the picture, title and line that WhatsApp, Facebook and X show when a store or
 * product link is pasted). Crawlers do not run JavaScript, so these are printed by the server in
 * resources/views/app.blade.php, from the `$meta` view data a controller passes with ->withViewData().
 */
class ShareMeta
{
    // f_jpg, not f_auto: chat apps' link crawlers do not always accept WebP/AVIF.
    private const FIT = 'f_jpg,q_auto,w_1200,h_630,c_fill,g_auto';

    /** Banner, else logo on a plain background, else the Porrop picture, so a link never goes out without an image. */
    private static function storeImage(Vendor $vendor): string
    {
        if ($vendor->banner_public_id) {
            return Cloudinary::url($vendor->banner_public_id, self::FIT);
        }

        if ($vendor->logo_public_id) {
            return Cloudinary::url($vendor->logo_public_id, 'f_jpg,q_auto,w_1200,h_630,c_pad,b_white');
        }

        return self::fallback();
    }

    public static function fallback(): string
    {
        return asset('brand/og-default.png');
    }

    public static function store(Vendor $vendor): array
    {
        $description = $vendor->tagline
            ?: Str::limit(trim(strip_tags((string) $vendor->description)), 150)
            ?: "Shop {$vendor->name} on Porrop. Order and pay online.";

        return [
            'title' => "{$vendor->name} on Porrop",
            'description' => $description,
            'url' => route('store.show', ['vendor' => $vendor]),
            'image' => self::storeImage($vendor),
        ];
    }

    public static function product(Vendor $vendor, Product $product): array
    {
        $product->loadMissing(['variants', 'images']);

        $price = $product->price_from;
        $line = $price !== null ? 'From ₦'.number_format($price).'. ' : '';
        $about = Str::limit(trim(strip_tags((string) $product->description)), 120);

        $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'title' => "{$product->name} - {$vendor->name}",
            'description' => trim($line.($about ?: "Order and pay online at {$vendor->name}.")),
            'url' => route('store.product', ['vendor' => $vendor, 'product' => $product]),
            'image' => $image ? Cloudinary::url($image->cloudinary_public_id, self::FIT) : self::fallback(),
        ];
    }
}
