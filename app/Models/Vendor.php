<?php

namespace App\Models;

use App\Support\Cloudinary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class Vendor extends Model implements Sitemapable
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name', 'slug', 'description', 'logo_public_id', 'banner_public_id',
        'email', 'phone', 'whatsapp_number', 'socials', 'status',
        'bank_name', 'bank_code', 'account_number', 'account_name', 'paystack_subaccount_code',
        'tagline', 'banner_public_id', 'listed_in_directory',
    ];

    protected $casts = [
        'socials' => 'array',
        'listed_in_directory' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ---------- Relationships ---------- */

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    // These are what `scopeBindings()` uses to resolve {product}, {order}, {variant}, {pickup_point}
    // inside a /store/{vendor}/... route, so another vendor's id gives a 404.
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pickupPoints(): HasMany
    {
        return $this->hasMany(PickupPoint::class);
    }

    /* ---------- Scopes / helpers ---------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return Cloudinary::url($this->logo_public_id, 'f_auto,q_auto,w_200,h_200,c_fill');
    }

    public function getBannerUrlAttribute(): ?string
    {
        return Cloudinary::url($this->banner_public_id, 'f_auto,q_auto,w_1600,h_480,c_fill,g_auto');
    }

    public function hasPayoutDetails(): bool
    {
        $saved = filled($this->bank_name) && filled($this->account_number) && filled($this->account_name);

        return config('porrop.payout_mode') === 'split' ? $saved && filled($this->paystack_subaccount_code) : $saved;
    }

    /**
     * The single place that decides which commission rate (percent) applies.
     * v1: one flat rate from config for everyone.
     * v2: return a per-vendor override or look up the vendor's plan here.
     */
    public function commissionRate(): float
    {
        return (float) config('porrop.commission.rate');
    }

    public function toSitemapTag(): Url|string|array
    {
        return Url::create(route('store.show', $this))
            ->setLastModificationDate($this->updated_at)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.7);
    }
}
