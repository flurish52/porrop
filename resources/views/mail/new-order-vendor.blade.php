<x-mail::message>
# New paid order {{ $order->reference }}

**{{ $order->full_name }}** just paid **₦{{ number_format((float) $order->total_amount, 2) }}** at {{ $vendor->name }}.

@foreach ($order->items as $item)
- {{ $item->quantity }} x {{ $item->product_name }}@if ($item->variant_name && $item->variant_name !== 'Default') ({{ $item->variant_name }})@endif - ₦{{ number_format((float) $item->price * $item->quantity, 2) }}
@endforeach

**Deliver to:** {{ $order->delivery_address }}

**Phone:** {{ $order->guest_phone }}

You will receive ₦{{ number_format((float) $order->vendor_payout, 2) }} (Porrop's fee is ₦{{ number_format((float) $order->commission_amount, 2) }}).

<x-mail::button :url="$url">
Open the order
</x-mail::button>

Please contact the customer and arrange delivery.
</x-mail::message>
