<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToVendor;

    public const STATUS_PENDING = 'pending';       // created, waiting for payment
    public const STATUS_PAID = 'paid';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';           // ready for pickup / out for delivery
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYOUT_PENDING = 'pending';
    public const PAYOUT_PAID = 'paid';

    protected $fillable = [
        'vendor_id', 'reference', 'user_id', 'guest_email', 'full_name', 'guest_phone',
        'fulfillment_type', 'pickup_point_id', 'delivery_address', 'status',
        'subtotal', 'total_amount', 'commission_rate', 'commission_amount', 'vendor_payout', 'payout_status',
        'received_at', 'received_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendor_payout' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function isPaid(): bool
    {
        return $this->status !== self::STATUS_PENDING && $this->status !== self::STATUS_CANCELLED;
    }
}
