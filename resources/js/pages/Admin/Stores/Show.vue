<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
    store: { type: Object, required: true },
    products: { type: Array, default: () => [] },
    backUrl: { type: String, required: true },
})

const naira = (n) => (n == null ? '-' : `₦${Number(n).toLocaleString('en-NG')}`)
const badge = (s) => (s === 'active' ? 'bg-primary-light text-primary-dark' : 'bg-neutral-bg text-neutral-text/70 ring-1 ring-primary/10')
</script>

<template>
    <Head :title="`${store.name} - Porrop admin`" />

    <Link :href="backUrl" class="text-sm font-semibold text-primary underline">&larr; All stores</Link>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
        <h1 class="min-w-0 break-words font-heading text-3xl font-semibold text-primary-dark">{{ store.name }}</h1>
        <a :href="store.url" target="_blank" rel="noopener" class="flex-none rounded-lg bg-accent px-4 py-2 text-sm font-bold text-ink">Open store</a>
    </div>

    <dl class="mt-5 grid gap-3 rounded-xl bg-white p-4 text-sm shadow-soft ring-1 ring-primary/5 sm:grid-cols-2">
        <div><dt class="text-xs text-neutral-text/60">Owner</dt><dd>{{ store.owner_name || '-' }}</dd></div>
        <div><dt class="text-xs text-neutral-text/60">Owner email</dt><dd class="break-all">{{ store.owner_email || '-' }}</dd></div>
        <div><dt class="text-xs text-neutral-text/60">Store contact</dt><dd class="break-all">{{ store.email || store.phone || '-' }}</dd></div>
        <div><dt class="text-xs text-neutral-text/60">Joined</dt><dd>{{ store.joined }} ({{ store.joined_ago }})</dd></div>
        <div><dt class="text-xs text-neutral-text/60">Status</dt><dd class="capitalize">{{ store.status }}</dd></div>
        <div><dt class="text-xs text-neutral-text/60">Payout details</dt><dd>{{ store.has_payout_details ? 'Saved' : 'Not added yet' }}</dd></div>
        <div><dt class="text-xs text-neutral-text/60">Products</dt><dd>{{ store.live_products }} live of {{ store.products }}</dd></div>
        <div><dt class="text-xs text-neutral-text/60">Paid orders</dt><dd>{{ store.paid_orders }}</dd></div>
    </dl>

    <h2 class="mt-8 font-heading text-xl font-semibold text-primary-dark">Products</h2>
    <p v-if="!products.length" class="mt-2 text-sm text-neutral-text/70">This store has not added any products yet.</p>
    <ul v-else class="mt-3 divide-y divide-primary/10 rounded-xl bg-white shadow-soft ring-1 ring-primary/5">
        <li v-for="p in products" :key="p.id" class="flex items-center gap-3 px-4 py-3">
            <div class="min-w-0 flex-1">
                <a :href="p.url" target="_blank" rel="noopener" class="block truncate font-medium hover:underline">{{ p.name }}</a>
                <p class="text-xs text-neutral-text/60">{{ p.variants }} variant<span v-if="p.variants !== 1">s</span> · {{ p.stock }} in stock · added {{ p.created_at }}</p>
            </div>
            <span class="flex-none text-sm font-semibold">{{ naira(p.price_from) }}</span>
            <span class="flex-none rounded-full px-2 py-0.5 text-xs font-semibold capitalize" :class="badge(p.status)">{{ p.status }}</span>
        </li>
    </ul>
</template>
