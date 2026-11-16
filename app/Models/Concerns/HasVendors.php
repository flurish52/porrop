<?php

namespace App\Models\Concerns;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasVendors
{
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class)->withPivot('role')->withTimestamps();
    }

    public function isVendorMember(): bool
    {
        return $this->vendors()->exists();
    }
}
