<script setup>
import { Head, Link } from '@inertiajs/vue3'
import VendorLayout from '@/Layouts/VendorLayout.vue'

defineOptions({ layout: VendorLayout })

defineProps({
    products: { type: Object, required: true }, // paginator: { data, next_page_url, prev_page_url }
    createUrl: { type: String, required: true },
    nav: { type: Object, default: () => ({}) },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`
</script>

<template>
    <Head title="Products" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-heading text-3xl font-semibold text-primary-dark">Products</h1>
        <Link :href="createUrl" class="rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-ink shadow-soft hover:bg-accent-light">
            Add product
        </Link>
    </div>

    <div v-if="!products.data.length" class="mt-6 rounded-2xl bg-white p-10 text-center shadow-soft">
        <p class="font-heading text-xl font-semibold text-primary-dark">Your shelf is empty</p>
        <p class="mt-2 text-sm text-neutral-text/70">Add your first product and it shows up in your store straight away.</p>
        <Link :href="createUrl" class="mt-5 inline-block rounded-xl bg-accent px-5 py-3 text-sm font-semibold text-ink shadow-soft hover:bg-accent-light">
            Add your first product
        </Link>
    </div>

    <ul v-else class="mt-6 space-y-3">
        <li v-for="p in products.data" :key="p.id">
            <Link :href="p.editUrl" class="flex items-center gap-4 rounded-2xl bg-white p-3 shadow-soft transition hover:shadow-soft-lg sm:p-4">
                <div class="h-16 w-16 flex-none overflow-hidden rounded-lg bg-secondary">
                    <img v-if="p.image" :src="p.image" alt="" class="h-full w-full object-cover" loading="lazy" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ p.name }}</p>
                    <p class="mt-0.5 text-sm text-neutral-text/70">
                        <span v-if="p.price_from !== null">{{ p.variant_count > 1 ? 'From ' : '' }}{{ naira(p.price_from) }}</span>
                        <span class="mx-1.5">&middot;</span>
                        <span :class="p.stock === 0 ? 'text-red-700' : ''">{{ p.stock === 0 ? 'Sold out' : `${p.stock} in stock` }}</span>
                        <span v-if="p.variant_count > 1"><span class="mx-1.5">&middot;</span>{{ p.variant_count }} options</span>
                    </p>
                </div>
                <span
                    class="flex-none rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="p.status === 'active' ? 'bg-primary-light text-primary-dark' : 'bg-secondary text-neutral-text/70'"
                >{{ p.status === 'active' ? 'Live' : 'Draft' }}</span>
            </Link>
        </li>
    </ul>

    <nav v-if="products.prev_page_url || products.next_page_url" class="mt-6 flex justify-between text-sm" aria-label="Pagination">
        <Link v-if="products.prev_page_url" :href="products.prev_page_url" class="font-medium text-primary underline">&larr; Newer</Link>
        <span v-else />
        <Link v-if="products.next_page_url" :href="products.next_page_url" class="font-medium text-primary underline">Older &rarr;</Link>
    </nav>
</template>
