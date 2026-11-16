<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import VendorLayout from '@/Layouts/VendorLayout.vue'

defineOptions({ layout: VendorLayout })

const props = defineProps({
    order: { type: Object, required: true },
    nextStatuses: { type: Array, default: () => [] },
    statusUrl: { type: String, required: true },
    backUrl: { type: String, required: true },
    nav: { type: Object, default: () => ({}) },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`
const LABEL = { paid: 'New order, paid', processing: 'Preparing', ready: 'Ready', completed: 'Completed', cancelled: 'Cancelled' }
const ACTION = { processing: 'Start preparing', ready: 'Mark as ready', completed: 'Mark as completed' }

const set = (status) => router.patch(props.statusUrl, { status }, { preserveScroll: true })

const chat = computed(() => {
    const phone = props.order.customer.phone
    return phone ? `https://wa.me/${phone}?text=${encodeURIComponent(`Hello ${props.order.customer.name}, about your order ${props.order.reference}.`)}` : ''
})
</script>

<template>
    <Head :title="`Order ${order.reference}`" />

    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <Link :href="backUrl" class="text-sm font-medium text-primary underline">&larr; All orders</Link>
            <h1 class="mt-2 font-heading text-3xl font-semibold text-primary-dark">{{ order.reference }}</h1>
            <p class="text-sm text-neutral-text/70">{{ LABEL[order.status] ?? order.status }} &middot; placed {{ order.placed_at }}</p>
        </div>

        <div v-if="nextStatuses.length" class="flex flex-wrap gap-2 rounded-2xl bg-white p-4 shadow-soft">
            <button
                v-for="(s, i) in nextStatuses"
                :key="s"
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-semibold"
                :class="i === 0 ? 'bg-accent text-ink hover:bg-accent-light' : 'border border-primary/25 text-primary-dark hover:bg-primary-light'"
                @click="set(s)"
            >{{ ACTION[s] }}</button>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-soft sm:p-6">
            <h2 class="font-heading text-lg font-semibold text-primary-dark">Deliver to</h2>
            <p class="mt-2 text-sm font-medium">{{ order.customer.name }}</p>
            <p class="mt-1 whitespace-pre-line text-sm text-neutral-text/80">{{ order.customer.address }}</p>
            <p class="mt-2 text-sm">{{ order.customer.phone }} &middot; {{ order.customer.email }}</p>
            <a v-if="chat" :href="chat" target="_blank" rel="noopener" class="mt-3 inline-block rounded-lg bg-whatsapp px-4 py-2 text-sm font-semibold text-ink hover:opacity-90">Message customer</a>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-soft sm:p-6">
            <h2 class="font-heading text-lg font-semibold text-primary-dark">Items</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li v-for="(i, k) in order.items" :key="k" class="flex justify-between gap-3">
                    <span class="min-w-0">{{ i.quantity }} &times; {{ i.name }}<span v-if="i.variant_name" class="text-neutral-text/60"> ({{ i.variant_name }})</span></span>
                    <span class="flex-none">{{ naira(i.price * i.quantity) }}</span>
                </li>
            </ul>

            <dl class="mt-4 space-y-1 border-t border-primary/10 pt-3 text-sm">
                <div class="flex justify-between"><dt>Customer paid</dt><dd>{{ naira(order.subtotal) }}</dd></div>
                <div class="flex justify-between text-neutral-text/70"><dt>Porrop fee ({{ order.commission_rate }}%, with min and max)</dt><dd>&minus; {{ naira(order.commission) }}</dd></div>
                <div class="flex justify-between text-base font-semibold"><dt>You get</dt><dd>{{ naira(order.payout) }}</dd></div>
                <p class="pt-1 text-xs text-neutral-text/60">Payout: {{ order.payout_status === 'paid' ? 'sent to your account' : 'waiting to be paid out' }}</p>
            </dl>
        </div>
    </div>
</template>
