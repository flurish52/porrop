<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToVendor, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DRAFT = 'draft';

    // vendor_id is fillable for services/tests. In controllers only pass validated fields:
    // the BelongsToVendor trait sets vendor_id from the current store on its own.
    protected $fillable = ['vendor_id', 'name', 'slug', 'description', 'status'];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('is_primary')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /** Lowest variant price, for "From ₦X" labels. Uses the loaded relation when available. */
    public function getPriceFromAttribute(): ?float
    {
        $min = $this->variants->min('price');

        return $min === null ? null : (float) $min;
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $image = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        return $image?->url;
    }

    public function getInStockAttribute(): bool
    {
        return $this->variants->sum('stock_quantity') > 0;
    }
}
