<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    stats: { type: Object, required: true },
    activity: { type: Array, default: () => [] },
    stores: { type: Array, default: () => [] },
    prevUrl: { type: String, default: null },
    nextUrl: { type: String, default: null },
    q: { type: String, default: '' },
    searchUrl: { type: String, required: true },
})

const term = ref(props.q)
const search = () => router.get(props.searchUrl, term.value ? { q: term.value } : {}, { preserveState: true })

const tiles = [
    { key: 'stores', label: 'Stores', sub: 'new_stores', subLabel: 'this week' },
    { key: 'with_products', label: 'Stores with products' },
    { key: 'products', label: 'Products', sub: 'new_products', subLabel: 'this week' },
    { key: 'paid_orders', label: 'Paid orders' },
]

const badge = (s) => (s === 'active' ? 'bg-primary-light text-primary-dark' : 'bg-amber-100 text-amber-900')
</script>

<template>
    <Head title="Stores - Porrop admin" />

    <h1 class="font-heading text-3xl font-semibold text-primary-dark">Stores</h1>

    <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div v-for="t in tiles" :key="t.key" class="rounded-xl bg-white p-4 shadow-soft ring-1 ring-primary/5">
            <dt class="text-xs font-medium uppercase tracking-wide text-neutral-text/60">{{ t.label }}</dt>
            <dd class="mt-1 font-heading text-3xl font-semibold text-primary-dark">{{ stats[t.key] }}</dd>
            <p v-if="t.sub" class="mt-0.5 text-xs text-neutral-text/60">+{{ stats[t.sub] }} {{ t.subLabel }}</p>
        </div>
    </dl>

    <section class="mt-8" aria-labelledby="activity-title">
        <h2 id="activity-title" class="font-heading text-xl font-semibold text-primary-dark">Recent activity</h2>
        <p v-if="!activity.length" class="mt-2 text-sm text-neutral-text/70">Nothing yet.</p>
        <ul v-else class="mt-3 divide-y divide-primary/10 rounded-xl bg-white shadow-soft ring-1 ring-primary/5">
            <li v-for="(a, i) in activity" :key="i" class="flex items-center gap-3 px-4 py-3 text-sm">
                <span class="flex-none rounded-full px-2 py-0.5 text-xs font-semibold" :class="a.type === 'store' ? 'bg-accent/30 text-ink' : 'bg-primary-light text-primary-dark'">
                    {{ a.type === 'store' ? 'New store' : 'New product' }}
                </span>
                <component :is="a.url ? Link : 'span'" :href="a.url" class="min-w-0 flex-1 break-words" :class="a.url ? 'hover:underline' : ''">{{ a.text }}</component>
                <span class="flex-none text-xs text-neutral-text/60">{{ a.when }}</span>
            </li>
        </ul>
    </section>

    <section class="mt-8" aria-labelledby="stores-title">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h2 id="stores-title" class="font-heading text-xl font-semibold text-primary-dark">All stores</h2>
            <form class="flex gap-2" @submit.prevent="search">
                <input v-model="term" type="search" placeholder="Search name or link" class="w-52 rounded-lg border border-primary/20 bg-white px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <button class="rounded-lg bg-primary-dark px-4 py-2 text-sm font-semibold text-white">Search</button>
            </form>
        </div>

        <p v-if="!stores.length" class="mt-4 text-sm text-neutral-text/70">No stores found.</p>

        <ul v-else class="mt-3 space-y-3">
            <li v-for="s in stores" :key="s.id" class="rounded-xl bg-white p-4 shadow-soft ring-1 ring-primary/5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <Link :href="s.admin_url" class="break-words font-semibold text-primary-dark hover:underline">{{ s.name }}</Link>
                        <p class="truncate text-xs text-neutral-text/60">{{ s.owner_name || 'No owner' }}<span v-if="s.owner_email"> · {{ s.owner_email }}</span></p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize" :class="badge(s.status)">{{ s.status }}</span>
                </div>
                <dl class="mt-3 grid grid-cols-3 gap-2 text-sm">
                    <div><dt class="text-xs text-neutral-text/60">Products</dt><dd class="font-semibold">{{ s.live_products }}<span class="font-normal text-neutral-text/60"> / {{ s.products }}</span></dd></div>
                    <div><dt class="text-xs text-neutral-text/60">Paid orders</dt><dd class="font-semibold">{{ s.paid_orders }}</dd></div>
                    <div><dt class="text-xs text-neutral-text/60">Joined</dt><dd class="font-semibold">{{ s.joined }}</dd></div>
                </dl>
                <p class="mt-2 text-xs text-neutral-text/60">{{ s.last_product_at ? `Last product added ${s.last_product_at}` : 'No products yet' }}</p>
            </li>
        </ul>

        <div v-if="prevUrl || nextUrl" class="mt-4 flex justify-between text-sm">
            <Link v-if="prevUrl" :href="prevUrl" class="font-semibold text-primary underline">&larr; Newer</Link><span v-else />
            <Link v-if="nextUrl" :href="nextUrl" class="font-semibold text-primary underline">Older &rarr;</Link>
        </div>
    </section>
</template>
