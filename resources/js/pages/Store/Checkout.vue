<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import StoreLayout from '@/Layouts/StoreLayout.vue'

defineOptions({ layout: StoreLayout })

const props = defineProps({
    lines: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    notice: { type: String, default: null },
    submitUrl: { type: String, required: true },
    prefill: { type: Object, default: () => ({}) },
    shop: { type: Object, default: () => ({}) },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`

const form = useForm({
    full_name: props.prefill.full_name ?? '',
    email: props.prefill.email ?? '',
    phone: props.prefill.phone ?? '',
    delivery_address: '',
})

const submit = () => form.post(props.submitUrl)
const field = 'mt-1 block w-full rounded-lg border-primary/20 bg-white shadow-sm focus:border-primary focus:ring-primary'
</script>

<template>
    <Head title="Checkout" />

    <Link :href="shop.cart_url || '#'" class="text-sm font-medium text-primary underline">&larr; Back to cart</Link>
    <h1 class="mt-2 font-heading text-3xl font-semibold text-primary-dark">Checkout</h1>

    <p v-if="notice" class="mt-4 rounded-lg bg-accent-light/60 px-4 py-3 text-sm text-ink" role="status">{{ notice }}</p>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-[minmax(0,1fr)_20rem]">
        <form class="space-y-5 rounded-2xl bg-white p-5 shadow-soft sm:p-6" @submit.prevent="submit">
            <div>
                <label for="full_name" class="text-sm font-medium">Full name</label>
                <input id="full_name" v-model="form.full_name" type="text" autocomplete="name" :class="field" />
                <p v-if="form.errors.full_name" class="mt-1 text-sm text-red-700">{{ form.errors.full_name }}</p>
            </div>
            <div>
                <label for="email" class="text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="email" :class="field" />
                <p class="mt-1 text-xs text-neutral-text/60">Your payment receipt goes here.</p>
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-700">{{ form.errors.email }}</p>
            </div>
            <div>
                <label for="phone" class="text-sm font-medium">Phone number</label>
                <input id="phone" v-model="form.phone" type="tel" inputmode="tel" autocomplete="tel" :class="field" placeholder="0803 123 4567" />
                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-700">{{ form.errors.phone }}</p>
            </div>
            <div>
                <label for="delivery_address" class="text-sm font-medium">Delivery address</label>
                <textarea id="delivery_address" v-model="form.delivery_address" rows="3" autocomplete="street-address" :class="field" placeholder="House number, street, area, city, state" />
                <p v-if="form.errors.delivery_address" class="mt-1 text-sm text-red-700">{{ form.errors.delivery_address }}</p>
            </div>

            <p v-if="form.errors.cart" class="text-sm text-red-700" role="alert">{{ form.errors.cart }}</p>
            <p v-if="form.errors.payment" class="text-sm text-red-700" role="alert">{{ form.errors.payment }}</p>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-accent px-5 py-3 text-base font-semibold text-ink shadow-soft transition hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60"
            >
                {{ form.processing ? 'Taking you to payment...' : `Pay ${naira(subtotal)}` }}
            </button>
            <p class="text-center text-xs text-neutral-text/60">You pay securely on Paystack. Card, transfer or USSD.</p>
        </form>

        <aside class="h-fit rounded-2xl bg-white p-5 shadow-soft">
            <h2 class="font-heading text-lg font-semibold text-primary-dark">Your order</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li v-for="l in lines" :key="l.variant_id" class="flex justify-between gap-3">
                    <span class="min-w-0">{{ l.quantity }} &times; {{ l.name }}<span v-if="l.variant_name" class="text-neutral-text/60"> ({{ l.variant_name }})</span></span>
                    <span class="flex-none">{{ naira(l.total) }}</span>
                </li>
            </ul>
            <div class="mt-4 flex justify-between border-t border-primary/10 pt-3 font-semibold"><span>Total</span><span>{{ naira(subtotal) }}</span></div>
            <p class="mt-2 text-xs text-neutral-text/60">Delivery is arranged with the seller after you pay.</p>
        </aside>
    </div>
</template>
