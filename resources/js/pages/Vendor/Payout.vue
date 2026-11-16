<script setup>
import { computed, nextTick, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import VendorLayout from '@/Layouts/VendorLayout.vue'

defineOptions({ layout: VendorLayout })

const props = defineProps({
    details: { type: Object, required: true },
    ready: { type: Boolean, default: false },
    banks: { type: Array, default: () => [] },
    bankError: { type: String, default: null },
    verifyAccounts: { type: Boolean, default: true },
    updateUrl: { type: String, required: true },
    checkUrl: { type: String, required: true },
    nav: { type: Object, default: () => ({}) },
})

const form = useForm({
    bank_code: props.details.bank_code,
    account_number: props.details.account_number,
    account_name: props.details.account_name,
    confirmed: false,
})

// A saved account is shown as a summary; the form only opens when they press "Change".
const editing = ref(!props.ready)
const checking = ref(false)
const checkError = ref('')
const found = ref(null) // { account_name, bank_name } while the confirmation box is open
const yesBtn = ref(null)

const last4 = computed(() => (props.details.account_number || '').slice(-4))
const field = 'mt-1 block w-full rounded-lg border-primary/20 bg-white shadow-sm focus:border-primary focus:ring-primary'

const save = () =>
    form.put(props.updateUrl, {
        preserveScroll: true,
        onSuccess: () => {
            found.value = null
            editing.value = false
            form.confirmed = false
        },
        onError: () => { found.value = null },
    })

// Step 1: ask the bank whose account this is.
const check = async () => {
    checkError.value = ''
    form.clearErrors()

    if (!props.verifyAccounts) {
        save()
        return
    }

    checking.value = true
    try {
        const { data } = await window.axios.get(props.checkUrl, {
            params: { bank_code: form.bank_code, account_number: form.account_number },
        })
        found.value = data
        await nextTick()
        yesBtn.value?.focus()
    } catch (e) {
        const res = e.response?.data
        checkError.value = res?.errors ? Object.values(res.errors).flat()[0] : (res?.message || 'We could not check that account. Try again.')
    } finally {
        checking.value = false
    }
}

// Step 2: they confirmed the name is theirs.
const confirmYes = () => {
    form.confirmed = true
    save()
}
</script>

<template>
    <Head title="Payout details" />

    <div class="mx-auto max-w-2xl">
        <h1 class="font-heading text-3xl font-semibold text-primary-dark">Payout details</h1>
        <p class="mt-1 text-sm text-neutral-text/70">
            When a customer pays, the money is split automatically. Porrop's fee goes to Porrop and the rest goes straight to this account. You do not need a Paystack account.
        </p>

        <p v-if="bankError" class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ bankError }}</p>

        <!-- Saved account summary -->
        <div v-if="ready && !editing" class="mt-6 rounded-2xl bg-white p-5 shadow-soft sm:p-6">
            <p class="text-xs font-medium uppercase tracking-wide text-neutral-text/60">Paying into</p>
            <p class="mt-1 text-xl font-semibold text-primary-dark">{{ details.account_name }}</p>
            <p class="text-sm text-neutral-text/70">{{ details.bank_name }} &middot; ending {{ last4 }}</p>
            <button type="button" class="mt-4 rounded-xl border border-primary/25 px-4 py-2 text-sm font-semibold text-primary-dark hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" @click="editing = true">
                Change bank account
            </button>
        </div>

        <template v-else>
            <p v-if="!ready" class="mt-4 rounded-lg bg-accent/20 px-4 py-3 text-sm text-ink" role="status">
                Customers cannot pay until you save your bank account here.
            </p>

            <form class="mt-6 space-y-5 rounded-2xl bg-white p-5 shadow-soft sm:p-6" @submit.prevent="check">
                <div>
                    <label for="bank_code" class="text-sm font-medium">Bank</label>
                    <select id="bank_code" v-model="form.bank_code" :class="field">
                        <option value="" disabled>Choose your bank</option>
                        <option v-for="b in banks" :key="b.code" :value="b.code">{{ b.name }}</option>
                    </select>
                    <p v-if="form.errors.bank_code" class="mt-1 text-sm text-red-700">{{ form.errors.bank_code }}</p>
                </div>
                <div>
                    <label for="account_number" class="text-sm font-medium">Account number</label>
                    <input id="account_number" v-model="form.account_number" type="text" inputmode="numeric" maxlength="10" autocomplete="off" :class="field" placeholder="10 digits" />
                    <p v-if="checkError || form.errors.account_number" class="mt-1 text-sm text-red-700" role="alert">{{ checkError || form.errors.account_number }}</p>
                </div>
                <div v-if="!verifyAccounts">
                    <label for="account_name" class="text-sm font-medium">Account name</label>
                    <input id="account_name" v-model="form.account_name" type="text" autocomplete="off" :class="field" placeholder="As it appears on your account" />
                    <p v-if="form.errors.account_name" class="mt-1 text-sm text-red-700">{{ form.errors.account_name }}</p>
                </div>
                <p v-if="form.errors.confirmed" class="text-sm text-red-700" role="alert">{{ form.errors.confirmed }}</p>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" :disabled="checking || form.processing || !banks.length || !form.bank_code || form.account_number.length !== 10" class="w-full rounded-xl bg-accent px-5 py-3 text-base font-semibold text-ink shadow-soft transition hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                        {{ checking ? 'Checking with your bank...' : verifyAccounts ? 'Check account' : 'Save payout details' }}
                    </button>
                    <button v-if="ready" type="button" class="text-sm font-medium text-primary underline" @click="editing = false">Cancel</button>
                </div>
            </form>
        </template>
    </div>

    <!-- Confirmation: the bank's own name for the account -->
    <div v-if="found" class="fixed inset-0 z-50 flex items-end justify-center bg-ink/60 p-4 sm:items-center" @keydown.esc="found = null">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-desc">
            <h2 id="confirm-title" class="font-heading text-xl font-semibold text-primary-dark">Is this your account?</h2>
            <div class="mt-4 rounded-xl border-2 border-accent bg-accent/10 px-4 py-4 text-center">
                <p class="text-xs font-medium uppercase tracking-wide text-neutral-text/60">Name on the account</p>
                <p class="mt-1 break-words text-2xl font-bold text-ink">{{ found.account_name }}</p>
                <p class="mt-1 text-sm text-neutral-text/70">{{ found.bank_name }} &middot; {{ form.account_number }}</p>
            </div>
            <p id="confirm-desc" class="mt-4 text-sm text-neutral-text/80">
                All your sales will be paid into this account. If the name is not yours, go back and fix the bank or account number. Money sent to a wrong account usually cannot be recovered.
            </p>
            <div class="mt-5 flex flex-col gap-2 sm:flex-row-reverse">
                <button ref="yesBtn" type="button" :disabled="form.processing" class="rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60" @click="confirmYes">
                    {{ form.processing ? 'Saving...' : 'Yes, this is my account' }}
                </button>
                <button type="button" class="rounded-xl border border-primary/25 px-5 py-3 text-sm font-semibold text-primary-dark hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" @click="found = null">
                    No, change details
                </button>
            </div>
        </div>
    </div>
</template>
