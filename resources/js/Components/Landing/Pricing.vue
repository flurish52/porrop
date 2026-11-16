<script setup>
/**
 * Pricing + "what do I keep?" calculator. Anchor target for #pricing.
 *
 * Same formula as app/Services/CommissionCalculator.php:
 *   fee = rate% of the order, never below `min`, never above `max`, never more than the order.
 * Pass the real numbers from config('porrop.commission') so this never drifts from the server.
 *
 * Showing the exact fee (including when the minimum kicks in on small orders) is on purpose:
 * a vendor who can see the maths trusts the price.
 */
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    rate: { type: Number, default: 10 },
    min: { type: Number, default: 100 },
    max: { type: Number, default: 5000 },
    heading: { type: String, default: 'Simple pricing. You only pay when you sell.' },
    signupHref: { type: String, default: '/vendor/register' },
    signupLabel: { type: String, default: 'Open my free store' },
    // Fill this in once you have decided who covers payment processor charges, e.g.
    // 'Card and transfer processing charges are covered by Porrop.' Hidden while empty.
    processingNote: { type: String, default: '' },
    quickAmounts: { type: Array, default: () => [3000, 12000, 50000, 120000] },
})

const naira = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 })
const fmt = (n) => naira.format(n)

const amount = ref(12000)

const display = computed(() => (amount.value ? amount.value.toLocaleString('en-NG') : ''))

const onInput = (e) => {
    const digits = e.target.value.replace(/[^\d]/g, '').slice(0, 9)
    amount.value = digits ? Number(digits) : 0
    e.target.value = display.value
}

const result = computed(() => {
    const a = amount.value
    if (!a) return null
    let fee = (a * props.rate) / 100
    const floored = fee < props.min
    const capped = fee > props.max
    fee = Math.min(Math.max(fee, props.min), props.max)
    fee = Math.min(fee, a)
    fee = Math.round(fee * 100) / 100
    return {
        fee,
        keep: Math.round((a - fee) * 100) / 100,
        percent: Math.round((fee / a) * 1000) / 10,
        floored,
        capped,
    }
})

const note = computed(() => {
    if (!result.value) return 'Type an order amount to see exactly what you would keep.'
    if (result.value.capped) return `Capped: you never pay more than ${fmt(props.max)} on one order.`
    if (result.value.floored) return `The ${fmt(props.min)} minimum applies on small orders, so this one works out to ${result.value.percent}%.`
    return `That is ${props.rate}% of this order.`
})
</script>

<template>
    <section id="pricing" class="scroll-mt-20 py-16 md:py-20">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-14">
            <!-- The plain statement -->
            <div>
                <h2 class="font-heading text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ heading }}</h2>
                <p class="mt-3 text-base leading-relaxed text-ink/70">
                    No setup fee and no monthly fee. We earn a small part of each sale, so we only do well when you do.
                </p>

                <ul class="mt-6 space-y-3 text-sm text-ink">
                    <li v-for="line in [
                        `${rate}% of each sale`,
                        `Minimum fee of ${fmt(min)} per order`,
                        `Never more than ${fmt(max)} on a single order`,
                        'No setup fee, no monthly fee',
                    ]" :key="line" class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary text-white" aria-hidden="true">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10" /></svg>
                        </span>
                        <span class="font-medium">{{ line }}</span>
                    </li>
                </ul>

                <p v-if="processingNote" class="mt-5 text-sm text-ink/60">{{ processingNote }}</p>
            </div>

            <!-- Calculator -->
            <div class="rounded-3xl border border-ink/10 bg-white p-5 shadow-soft-lg sm:p-7">
                <label for="order-amount" class="text-sm font-semibold text-ink">If a customer orders for…</label>
                <div class="mt-2 flex items-center rounded-xl border border-ink/15 bg-neutral-bg px-4 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/30">
                    <span class="font-heading text-2xl font-semibold text-ink/60" aria-hidden="true">₦</span>
                    <input
                        id="order-amount"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        :value="display"
                        placeholder="0"
                        class="w-full border-0 bg-transparent px-2 py-3 text-2xl font-semibold tabular-nums text-ink placeholder:text-ink/30 focus:outline-none focus:ring-0"
                        @input="onInput"
                    />
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="q in quickAmounts"
                        :key="q"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        :class="amount === q ? 'border-primary bg-primary text-white' : 'border-ink/15 bg-white text-ink/80 hover:bg-primary-light'"
                        @click="amount = q"
                    >
                        {{ fmt(q) }}
                    </button>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3" aria-live="polite">
                    <div class="rounded-2xl bg-neutral-bg p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink/55">Porrop fee</p>
                        <p class="mt-1 font-heading text-xl font-semibold tabular-nums text-ink sm:text-2xl">
                            {{ result ? fmt(result.fee) : '–' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-accent p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink/70">You keep</p>
                        <p class="mt-1 font-heading text-xl font-semibold tabular-nums text-ink sm:text-2xl">
                            {{ result ? fmt(result.keep) : '–' }}
                        </p>
                    </div>
                </div>

                <p class="mt-4 text-sm leading-relaxed text-ink/65">{{ note }}</p>

                <Link
                    :href="signupHref"
                    class="mt-5 flex w-full items-center justify-center rounded-xl bg-primary px-6 py-3.5 text-base font-semibold text-white shadow-soft transition duration-200 hover:bg-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    {{ signupLabel }}
                </Link>
            </div>
        </div>
    </section>
</template>
