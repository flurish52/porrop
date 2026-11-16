<?php

namespace App\Models\Concerns;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Add to every model that has a vendor_id column (Product, ProductVariant, Order, PickupPoint).
 *
 * While a vendor is bound as 'currentVendor' (ResolveVendor middleware), every query is limited to
 * that vendor and new rows get its vendor_id. Outside a /store/{vendor} request nothing is bound,
 * so queries are NOT scoped: platform admin, Paystack webhook, customer account.
 * Be deliberate there. ->withoutGlobalScope('vendor') is the explicit way to say "all vendors".
 *
 * Queue jobs have no request: bind the vendor yourself at the top of handle():
 *   app()->instance('currentVendor', Vendor::findOrFail($this->vendorId));
 */
trait BelongsToVendor
{
    protected static function bootBelongsToVendor(): void
    {
        static::addGlobalScope('vendor', function (Builder $query) {
            if (app()->bound('currentVendor')) {
                $query->where(
                    $query->getModel()->qualifyColumn('vendor_id'),
                    app('currentVendor')->id
                );
            }
        });

        static::creating(function ($model) {
            if (! $model->vendor_id && app()->bound('currentVendor')) {
                $model->vendor_id = app('currentVendor')->id;
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
