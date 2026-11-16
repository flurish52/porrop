<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Platform-owned and shared by every store. Managed by the platform admin only.
class Category extends Model
{
    protected $fillable = ['name', 'slug', 'image_public_id'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('is_primary')->withTimestamps();
    }
}
