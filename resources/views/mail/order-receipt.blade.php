<x-mail::message :brand="$vendor->name" :brand-url="route('store.show', ['vendor' => $vendor])">
# Thank you, {{ $order->full_name }}

We received your payment of **₦{{ number_format((float) $order->total_amount, 2) }}** for order **{{ $order->reference }}** from {{ $vendor->name }}.

@foreach ($order->items as $item)
- {{ $item->quantity }} x {{ $item->product_name }}@if ($item->variant_name && $item->variant_name !== 'Default') ({{ $item->variant_name }})@endif - ₦{{ number_format((float) $item->price * $item->quantity, 2) }}
@endforeach

**Delivery to:** {{ $order->delivery_address }}

The seller will contact you on {{ $order->guest_phone }} to arrange delivery.

<x-mail::button :url="$url">
View your order
</x-mail::button>

@if ($vendor->email)
Questions about your order? Just reply to this email.

@endif
Thanks for shopping with {{ $vendor->name }}.
</x-mail::message>
