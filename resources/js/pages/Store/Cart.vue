<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import StoreLayout from '@/Layouts/StoreLayout.vue'

defineOptions({ layout: StoreLayout })

defineProps({
    lines: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    notice: { type: String, default: null },
    checkoutUrl: { type: String, required: true },
    shop: { type: Object, default: () => ({}) },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`

const setQty = (line, e) => {
    const q = Math.max(0, Math.min(Number(e.target.value) || 0, line.stock))
    router.patch(line.update_url, { quantity: q }, { preserveScroll: true })
}
const remove = (line) => router.delete(line.remove_url, { preserveScroll: true })
</script>

<template>
    <Head title="Your cart" />

    <h1 class="font-heading text-3xl font-semibold text-primary-dark">Your cart</h1>

    <p v-if="notice" class="mt-4 rounded-lg bg-accent-light/60 px-4 py-3 text-sm text-ink" role="status">{{ notice }}</p>

    <div v-if="!lines.length" class="mt-6 rounded-2xl bg-white p-10 text-center shadow-soft">
        <p class="text-sm text-neutral-text/70">Your cart is empty.</p>
        <Link :href="shop.url || '/'" class="mt-4 inline-block rounded-xl bg-accent px-5 py-3 text-sm font-semibold text-ink shadow-soft hover:bg-accent-light">Keep shopping</Link>
    </div>

    <div v-else class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-[minmax(0,1fr)_20rem]">
        <ul class="space-y-3">
            <li v-for="l in lines" :key="l.variant_id" class="flex gap-4 rounded-2xl bg-white p-3 shadow-soft sm:p-4">
                <Link :href="l.product_url" class="h-20 w-20 flex-none overflow-hidden rounded-lg bg-secondary">
                    <img v-if="l.image" :src="l.image" alt="" class="h-full w-full object-cover" loading="lazy" />
                </Link>
                <div class="min-w-0 flex-1">
                    <Link :href="l.product_url" class="block truncate font-medium hover:underline">{{ l.name }}</Link>
                    <p v-if="l.variant_name" class="text-sm text-neutral-text/70">{{ l.variant_name }}</p>
                    <p class="mt-1 text-sm">{{ naira(l.price) }}</p>
                    <div class="mt-2 flex items-center gap-3">
                        <label :for="`q-${l.variant_id}`" class="sr-only">Quantity</label>
                        <input :id="`q-${l.variant_id}`" :value="l.quantity" type="number" min="1" :max="l.stock" class="w-20 rounded-lg border-primary/20 py-1 text-sm focus:border-primary focus:ring-primary" @change="setQty(l, $event)" />
                        <button type="button" class="text-sm font-medium text-red-700 hover:underline" @click="remove(l)">Remove</button>
                    </div>
                </div>
                <p class="flex-none font-semibold">{{ naira(l.total) }}</p>
            </li>
        </ul>

        <aside class="h-fit rounded-2xl bg-white p-5 shadow-soft">
            <div class="flex justify-between text-sm"><span>Subtotal</span><span class="font-semibold">{{ naira(subtotal) }}</span></div>
            <p class="mt-2 text-xs text-neutral-text/60">Delivery is arranged with the seller after you pay.</p>
            <Link :href="route('store.checkout')" class="mt-4 block rounded-xl bg-accent px-5 py-3 text-center text-base font-semibold text-ink shadow-soft hover:bg-accent-light">Checkout</Link>
        </aside>
    </div>
</template>
