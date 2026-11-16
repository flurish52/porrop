<?php

namespace App\Models;

use App\Support\Cloudinary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// No vendor_id here: an image belongs to a product, and the product belongs to the vendor.
class ProductImage extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'product_variant_id', 'cloudinary_public_id', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getUrlAttribute(): ?string
    {
        return Cloudinary::url($this->cloudinary_public_id);
    }
}
