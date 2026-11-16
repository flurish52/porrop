<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    use BelongsToVendor;

    protected $fillable = ['vendor_id', 'name', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
