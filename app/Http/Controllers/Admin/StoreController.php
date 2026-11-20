<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Platform admin only (route middleware). Read-only view of every store. No tenant is bound, so no scope applies. */
class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 60));
        $week = now()->subDays(7);

        $stores = Vendor::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('slug', 'like', $like));
            })
            ->withCount([
                'products as products_count',
                'products as live_products_count' => fn ($p) => $p->where('status', 'active'),
                'orders as paid_orders_count' => fn ($o) => $o->whereIn('status', PayoutService::COUNTED),
            ])
            ->withMax('products as last_product_at', 'created_at')
            ->with(['users' => fn ($u) => $u->wherePivot('role', 'owner')])
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Vendor $v) => $this->row($v));

        return Inertia::render('Admin/Stores/Index', [
            'stats' => [
                'stores' => Vendor::count(),
                'new_stores' => Vendor::where('created_at', '>=', $week)->count(),
                'with_products' => Vendor::whereHas('products')->count(),
                'products' => Product::count(),
                'new_products' => Product::where('created_at', '>=', $week)->count(),
                'paid_orders' => Order::whereIn('status', PayoutService::COUNTED)->count(),
            ],
            'activity' => $this->activity(),
            'stores' => $stores->items(),
            'prevUrl' => $stores->previousPageUrl(),
            'nextUrl' => $stores->nextPageUrl(),
            'q' => $q,
            'searchUrl' => route('admin.stores.index'),
        ]);
    }

    public function show(Vendor $vendor): Response
    {
        $vendor->loadCount([
            'products as products_count',
            'products as live_products_count' => fn ($p) => $p->where('status', 'active'),
            'orders as paid_orders_count' => fn ($o) => $o->whereIn('status', PayoutService::COUNTED),
        ])->load(['users' => fn ($u) => $u->wherePivot('role', 'owner')]);

        $products = Product::where('vendor_id', $vendor->id)
            ->with('variants')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
                'price_from' => $p->price_from,
                'stock' => (int) $p->variants->sum('stock_quantity'),
                'variants' => $p->variants->count(),
                'created_at' => $p->created_at->format('d M Y, g:ia'),
                'url' => route('store.product', ['vendor' => $vendor, 'product' => $p]),
            ]);

        return Inertia::render('Admin/Stores/Show', [
            'store' => $this->row($vendor) + [
                'email' => $vendor->email,
                'phone' => $vendor->whatsapp_number ?: $vendor->phone,
                'has_payout_details' => $vendor->hasPayoutDetails(),
            ],
            'products' => $products,
            'backUrl' => route('admin.stores.index'),
        ]);
    }

    private function row(Vendor $v): array
    {
        $owner = $v->users->first();

        return [
            'id' => $v->id,
            'name' => $v->name,
            'status' => $v->status,
            'url' => route('store.show', ['vendor' => $v]),
            'admin_url' => route('admin.stores.show', ['vendor' => $v]),
            'owner_name' => $owner?->name,
            'owner_email' => $owner?->email,
            'products' => (int) ($v->products_count ?? 0),
            'live_products' => (int) ($v->live_products_count ?? 0),
            'paid_orders' => (int) ($v->paid_orders_count ?? 0),
            'last_product_at' => $v->last_product_at ? \Illuminate\Support\Carbon::parse($v->last_product_at)->diffForHumans() : null,
            'joined' => $v->created_at->format('d M Y'),
            'joined_ago' => $v->created_at->diffForHumans(),
        ];
    }

    /** The latest sign-ups and new products, newest first. */
    private function activity(): array
    {
        $signups = Vendor::latest('id')->limit(10)->get()->map(fn (Vendor $v) => [
            'type' => 'store',
            'text' => "{$v->name} opened a store",
            'url' => route('admin.stores.show', ['vendor' => $v]),
            'at' => $v->created_at,
        ]);

        $products = Product::with('vendor')->latest('id')->limit(10)->get()->map(fn (Product $p) => [
            'type' => 'product',
            'text' => ($p->vendor?->name ?? 'A store')." added \"{$p->name}\"",
            'url' => $p->vendor ? route('admin.stores.show', ['vendor' => $p->vendor]) : null,
            'at' => $p->created_at,
        ]);

        return $signups->concat($products)
            ->sortByDesc('at')
            ->take(12)
            ->map(fn ($a) => ['type' => $a['type'], 'text' => $a['text'], 'url' => $a['url'], 'when' => $a['at']->diffForHumans()])
            ->values()
            ->all();
    }
}
