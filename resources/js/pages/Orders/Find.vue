<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import FocusLayout from '@/Layouts/FocusLayout.vue'

defineOptions({ layout: FocusLayout })

const form = useForm({ email: '', reference: '' })

const submit = () =>
    form.post('/orders/find', {
        preserveScroll: true,
        onSuccess: () => form.reset('reference'),
    })

const field = 'mt-1 block w-full rounded-lg border-primary/20 bg-white shadow-sm focus:border-primary focus:ring-primary'
</script>

<template>
    <Head title="Find my order" />

    <div class="mx-auto max-w-md">
        <h1 class="font-heading text-3xl font-semibold text-primary-dark">Find my order</h1>
        <p class="mt-2 text-sm leading-relaxed text-neutral-text/75">
            Enter the email you used and your order reference. We will email you a private link to your order.
        </p>

        <form class="mt-6 space-y-5 rounded-2xl bg-white p-5 shadow-soft sm:p-6" @submit.prevent="submit">
            <div>
                <label for="email" class="text-sm font-medium">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="email" required :class="field" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-700" role="alert">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="reference" class="text-sm font-medium">Order reference</label>
                <input id="reference" v-model="form.reference" type="text" autocomplete="off" autocapitalize="characters" placeholder="PRP-AB12CD34" required :class="field" />
                <p class="mt-1 text-xs text-neutral-text/60">It starts with PRP- and is in your order confirmation email.</p>
                <p v-if="form.errors.reference" class="mt-1 text-sm text-red-700" role="alert">{{ form.errors.reference }}</p>
            </div>

            <button type="submit" :disabled="form.processing" class="w-full rounded-xl bg-accent px-5 py-3 text-base font-bold text-ink shadow-soft transition hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60">
                {{ form.processing ? 'Sending...' : 'Email me the link' }}
            </button>
        </form>

        <p class="mt-6 text-sm text-neutral-text/70">
            Cannot find your reference? Contact the seller on the number or WhatsApp link on their store page and tell them the email you used.
        </p>
    </div>
</template>
