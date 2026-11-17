<x-mail::message :brand="$vendor->name" :brand-url="route('store.show', ['vendor' => $vendor])">
# Your order {{ $order->reference }}

Hi {{ $order->full_name }}, you asked for the link to your order from {{ $vendor->name }}.

<x-mail::button :url="$url">
View your order
</x-mail::button>

Keep this link private. Anyone who has it can see the order.

If you did not ask for this, you can ignore this email. Nothing has changed on your order.
</x-mail::message>
