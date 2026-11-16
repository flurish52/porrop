<script setup>
import { Head, Link } from '@inertiajs/vue3'
import VendorLayout from '@/Layouts/VendorLayout.vue'

defineOptions({ layout: VendorLayout })

defineProps({
    orders: { type: Object, required: true }, // paginator
    nav: { type: Object, default: () => ({}) },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`
const LABEL = { paid: 'New', processing: 'Preparing', ready: 'Ready', completed: 'Completed', cancelled: 'Cancelled' }
const TONE = {
    paid: 'bg-accent-light text-ink',
    processing: 'bg-primary-light text-primary-dark',
    ready: 'bg-primary-light text-primary-dark',
    completed: 'bg-secondary text-neutral-text/70',
    cancelled: 'bg-red-50 text-red-800',
}
</script>

<template>
    <Head title="Orders" />

    <h1 class="font-heading text-3xl font-semibold text-primary-dark">Orders</h1>

    <div v-if="!orders.data.length" class="mt-6 rounded-2xl bg-white p-10 text-center shadow-soft">
        <p class="font-heading text-xl font-semibold text-primary-dark">No orders yet</p>
        <p class="mt-2 text-sm text-neutral-text/70">Share your store link on WhatsApp. Paid orders show up here.</p>
    </div>

    <ul v-else class="mt-6 space-y-3">
        <li v-for="o in orders.data" :key="o.id">
            <Link :href="o.url" class="flex items-center gap-4 rounded-2xl bg-white p-4 shadow-soft transition hover:shadow-soft-lg">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ o.customer }} <span class="font-normal text-neutral-text/60">&middot; {{ o.reference }}</span></p>
                    <p class="mt-0.5 text-sm text-neutral-text/70">{{ o.items }} {{ o.items === 1 ? 'item' : 'items' }} &middot; {{ o.placed_at }}</p>
                </div>
                <div class="flex-none text-right">
                    <p class="font-semibold">{{ naira(o.total) }}</p>
                    <p class="text-xs text-neutral-text/60">You get {{ naira(o.payout) }}</p>
                </div>
                <span class="flex-none rounded-full px-2.5 py-1 text-xs font-medium" :class="TONE[o.status]">{{ LABEL[o.status] ?? o.status }}</span>
            </Link>
        </li>
    </ul>

    <nav v-if="orders.prev_page_url || orders.next_page_url" class="mt-6 flex justify-between text-sm" aria-label="Pagination">
        <Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="font-medium text-primary underline">&larr; Newer</Link>
        <span v-else />
        <Link v-if="orders.next_page_url" :href="orders.next_page_url" class="font-medium text-primary underline">Older &rarr;</Link>
    </nav>
</template>
