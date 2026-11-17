<script setup>
import { computed, ref } from 'vue'
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

// The receipt email goes out once payment is confirmed
const emailSent = computed(() => ['paid', 'processing', 'ready', 'completed'].includes(props.order.status))

// If the controller sends order.email, show it partly hidden (ada@gmail.com -> a••@gmail.com)
const maskedEmail = computed(() => {
    const e = props.order.email
    if (!e || !e.includes('@')) return ''
    const [name, domain] = e.split('@')
    return `${name.slice(0, 1)}${'•'.repeat(Math.max(name.length - 1, 2))}@${domain}`
})

const copied = ref(false)
const copyId = async () => {
    try {
        await navigator.clipboard.writeText(props.order.reference)
    } catch {
        const el = document.getElementById('order-ref')
        if (el) {
            const range = document.createRange()
            range.selectNodeContents(el)
            const sel = window.getSelection()
            sel.removeAllRanges()
            sel.addRange(range)
            document.execCommand?.('copy')
        }
    }
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}
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

        <!-- Keep your order ID -->
        <section class="rounded-2xl border border-accent/60 bg-accent/10 p-5 sm:p-6" aria-labelledby="keep-id-title">
            <h2 id="keep-id-title" class="font-heading text-lg font-semibold text-primary-dark">Keep your order ID</h2>
            <p class="mt-1 text-sm text-neutral-text/80">
                You will need it to track this order. Take a screenshot of this page or save the ID below.
            </p>

            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                <p id="order-ref" class="select-all rounded-lg bg-white px-4 py-2.5 text-center font-mono text-base font-semibold tracking-wide text-primary-dark sm:flex-1 sm:text-left">{{ order.reference }}</p>
                <button
                    type="button"
                    class="rounded-lg border border-primary/25 bg-white px-4 py-2.5 text-sm font-semibold text-primary-dark hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    @click="copyId"
                >
                    <span aria-live="polite">{{ copied ? 'Copied' : 'Copy ID' }}</span>
                </button>
            </div>

            <p v-if="emailSent" class="mt-3 text-sm text-neutral-text/80">
                We have also sent a copy of this order to the email you entered at checkout<span v-if="maskedEmail"> ({{ maskedEmail }})</span>. If you can't see it, check your spam folder.
            </p>

            <p class="mt-3 text-sm text-neutral-text/80">
                Lost it? Track your order any time on the
                <a href="/orders/find" class="font-medium text-primary underline">order tracking page</a>.
            </p>
        </section>

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
