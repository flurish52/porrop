<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    // The table only has created_at (set by the database), no updated_at.
    public $timestamps = false;

    protected $fillable = ['order_id', 'status', 'changed_by'];

    protected $casts = ['created_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
