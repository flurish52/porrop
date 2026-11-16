<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import StoreLayout from '@/Layouts/StoreLayout.vue'

defineOptions({ layout: StoreLayout })

const props = defineProps({
    order: { type: Object, required: true },
    shop: { type: Object, default: () => ({}) },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`

const STATUS = {
    pending: ['Waiting for payment', "We haven't received your payment yet. If you were charged, refresh this page in a minute."],
    paid: ['Payment received', 'The seller has your order and will prepare it.'],
    processing: ['Being prepared', 'The seller is getting your order ready.'],
    ready: ['Ready', 'Your order is ready. The seller will arrange delivery.'],
    completed: ['Completed', 'This order is complete. Thank you!'],
    cancelled: ['Cancelled', 'This order was cancelled.'],
}
const status = computed(() => STATUS[props.order.status] ?? [props.order.status, ''])

const chatHref = computed(() =>
    props.shop.whatsapp_number
        ? `https://wa.me/${props.shop.whatsapp_number}?text=${encodeURIComponent(`Hello, about my order ${props.order.reference}.`)}`
        : '',
)
</script>

<template>
    <Head :title="`Order ${order.reference}`" />

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="rounded-2xl bg-white p-5 shadow-soft sm:p-6">
            <p class="text-sm text-neutral-text/60">Order {{ order.reference }}</p>
            <h1 class="mt-1 font-heading text-3xl font-semibold text-primary-dark">{{ status[0] }}</h1>
            <p class="mt-2 text-sm text-neutral-text/80">{{ status[1] }}</p>
            <a v-if="chatHref" :href="chatHref" target="_blank" rel="noopener" class="mt-4 inline-block rounded-lg bg-whatsapp px-4 py-2 text-sm font-semibold text-ink hover:opacity-90">
                Message the seller
            </a>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-soft sm:p-6">
            <h2 class="font-heading text-lg font-semibold text-primary-dark">Items</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li v-for="(i, k) in order.items" :key="k" class="flex justify-between gap-3">
                    <span class="min-w-0">{{ i.quantity }} &times; {{ i.name }}<span v-if="i.variant_name" class="text-neutral-text/60"> ({{ i.variant_name }})</span></span>
                    <span class="flex-none">{{ naira(i.price * i.quantity) }}</span>
                </li>
            </ul>
            <div class="mt-4 flex justify-between border-t border-primary/10 pt-3 font-semibold"><span>Total paid</span><span>{{ naira(order.total) }}</span></div>
        </div>

        <div class="rounded-2xl bg-white p-5 text-sm shadow-soft sm:p-6">
            <h2 class="font-heading text-lg font-semibold text-primary-dark">Delivery</h2>
            <p class="mt-2">{{ order.full_name }} &middot; {{ order.phone }}</p>
            <p class="mt-1 whitespace-pre-line text-neutral-text/80">{{ order.delivery_address }}</p>
            <p class="mt-3 text-xs text-neutral-text/60">Placed {{ order.placed_at }}</p>
        </div>

        <Link :href="shop.url || '/'" class="inline-block text-sm font-medium text-primary underline">&larr; Back to store</Link>
    </div>
</template>
