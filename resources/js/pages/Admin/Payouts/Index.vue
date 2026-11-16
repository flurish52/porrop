<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
    vendors: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
    holdDays: { type: Number, default: 3 },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`

const page = usePage()
const error = computed(() => page.props.errors?.payout || page.props.errors?.expected_amount || page.props.errors?.reference)

const refs = reactive({})
const busy = ref(null)
const copied = ref(null)

const copy = async (text, key) => {
    try {
        await navigator.clipboard.writeText(text)
        copied.value = key
        setTimeout(() => (copied.value = null), 1500)
    } catch {
        window.prompt('Copy:', text)
    }
}

const pay = (v) => {
    if (!confirm(`Confirm you have already sent ${naira(v.owed)} to ${v.account_name} (${v.bank_name}, ${v.account_number})?`)) return
    busy.value = v.id
    router.post(v.pay_url, { expected_amount: v.owed, reference: refs[v.id] || null }, {
        preserveScroll: true,
        onFinish: () => (busy.value = null),
    })
}
</script>

<template>
    <Head title="Payouts" />

    <h1 class="font-heading text-3xl font-semibold text-primary-dark">Vendor payouts</h1>
    <p class="mt-1 text-sm text-neutral-text/70">
        Send the money from your bank first, then press <strong>Mark as paid</strong> to record it.
        An order is ready once the vendor marks it completed, or {{ holdDays }} days after the customer paid.
    </p>

    <p v-if="error" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p>

    <div v-if="!vendors.length" class="mt-6 rounded-2xl bg-white p-10 text-center shadow-soft">
        <p class="font-heading text-xl font-semibold text-primary-dark">Nobody is owed anything</p>
        <p class="mt-2 text-sm text-neutral-text/70">Paid orders appear here once they are ready to pay out.</p>
    </div>

    <ul v-else class="mt-6 space-y-4">
        <li v-for="v in vendors" :key="v.id" class="rounded-2xl bg-white p-5 shadow-soft">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <a :href="v.store_url" target="_blank" rel="noopener" class="font-heading text-xl font-semibold text-primary-dark hover:underline">{{ v.name }}</a>
                    <p class="mt-1 text-sm text-neutral-text/70">
                        {{ v.owed_orders }} {{ v.owed_orders === 1 ? 'order' : 'orders' }} ready
                        <span v-if="v.held_orders"> &middot; {{ naira(v.held) }} on hold ({{ v.held_orders }})</span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-neutral-text/60">Pay now</p>
                    <p class="text-2xl font-semibold" :class="v.owed ? 'text-primary-dark' : 'text-neutral-text/40'">{{ naira(v.owed) }}</p>
                </div>
            </div>

            <div class="mt-4 rounded-xl bg-secondary-light p-4 text-sm">
                <template v-if="v.has_details">
                    <p class="font-medium">{{ v.account_name }}</p>
                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span>{{ v.bank_name }}</span>
                        <span class="font-mono text-base tracking-wide">{{ v.account_number }}</span>
                        <button type="button" class="font-medium text-primary underline" @click="copy(v.account_number, `n${v.id}`)">
                            {{ copied === `n${v.id}` ? 'Copied' : 'Copy number' }}
                        </button>
                        <button type="button" class="font-medium text-primary underline" @click="copy(String(v.owed), `a${v.id}`)">
                            {{ copied === `a${v.id}` ? 'Copied' : 'Copy amount' }}
                        </button>
                    </p>
                </template>
                <p v-else class="font-medium text-red-800">No bank details yet. Ask this vendor to add them under Payouts.</p>
            </div>

            <div v-if="v.owed > 0" class="mt-4 flex flex-wrap items-end gap-3">
                <div class="min-w-0 basis-full sm:basis-0 sm:flex-1">
                    <label :for="`ref-${v.id}`" class="text-sm font-medium">Transfer reference <span class="font-normal text-neutral-text/50">(optional)</span></label>
                    <input :id="`ref-${v.id}`" v-model="refs[v.id]" type="text" maxlength="120" class="mt-1 block w-full rounded-lg border-primary/20 shadow-sm focus:border-primary focus:ring-primary" placeholder="From your bank app" />
                </div>
                <button
                    type="button"
                    :disabled="!v.has_details || busy === v.id"
                    class="rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-ink shadow-soft hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="pay(v)"
                >{{ busy === v.id ? 'Saving...' : `Mark ${naira(v.owed)} as paid` }}</button>
            </div>
        </li>
    </ul>

    <h2 class="mt-10 font-heading text-2xl font-semibold text-primary-dark">Recent payouts</h2>
    <p v-if="!history.length" class="mt-2 text-sm text-neutral-text/70">None yet.</p>
    <ul v-else class="mt-3 divide-y divide-primary/10 rounded-2xl bg-white shadow-soft">
        <li v-for="h in history" :key="h.id" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
            <div class="min-w-0">
                <p class="font-medium">{{ h.vendor }} <span class="font-normal text-neutral-text/60">&middot; {{ h.order_count }} {{ h.order_count === 1 ? 'order' : 'orders' }}</span></p>
                <p class="text-xs text-neutral-text/60">{{ h.paid_at }}<span v-if="h.by"> &middot; by {{ h.by }}</span><span v-if="h.reference"> &middot; ref {{ h.reference }}</span></p>
            </div>
            <p class="font-semibold">{{ naira(h.amount) }}</p>
        </li>
    </ul>
</template>
