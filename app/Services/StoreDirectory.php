<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * What the public stores page (and the home page strip) shows. This is the one place that decides which
 * stores are listed, so the rules cannot drift apart:
 *   - the store is active and has not opted out,
 *   - it has at least one live product,
 *   - in split mode, it can actually take payments (bank account saved).
 *
 * It reads across ALL vendors on purpose, so every Product query says withoutGlobalScope('vendor').
 */
class StoreDirectory
{
    private const PRODUCTS_PER_CARD = 4;

    public function page(?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        return $this->listed($search)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Vendor $v) => $this->card($v, self::PRODUCTS_PER_CARD));
    }

    /** A few stores for the home page. @return list<array> */
    public function featured(int $limit = 6): array
    {
        return $this->listed()
            ->limit($limit)
            ->get()
            ->map(fn (Vendor $v) => $this->card($v, 3))
            ->all();
    }

    private function listed(?string $search = null): Builder
    {
        $live = fn ($q) => $q->withoutGlobalScope('vendor')->active();

        return Vendor::query()
            ->active()
            ->where('listed_in_directory', true)
            ->whereHas('products', $live)
            ->when(config('porrop.payout_mode') === 'split', fn ($q) => $q->whereNotNull('paystack_subaccount_code'))
            ->when(filled($search), function ($q) use ($search) {
                $like = '%'.addcslashes(trim($search), '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('tagline', 'like', $like));
            })
            ->withCount(['products as products_count' => $live])
            ->withMax(['products as last_product_at' => $live], 'created_at')
            ->orderByDesc('last_product_at')
            ->orderByDesc('id');
    }

    private function card(Vendor $v, int $productLimit): array
    {
        $products = Product::withoutGlobalScope('vendor')
            ->active()
            ->where('vendor_id', $v->id)
            ->with(['variants', 'images'])
            ->latest('id')
            ->limit($productLimit)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price_from' => $p->price_from,
                'image' => $p->primary_image_url,
                'url' => route('store.product', ['vendor' => $v->slug, 'product' => $p->slug]),
            ])
            ->values()
            ->all();

        return [
            'id' => $v->id,
            'name' => $v->name,
            'tagline' => $v->tagline ?: Str::limit(trim(strip_tags((string) $v->description)), 90),
            'logo' => $v->logo_url,
            'url' => route('store.show', ['vendor' => $v->slug]),
            'product_count' => (int) $v->products_count,
            'products' => $products,
        ];
    }
}
