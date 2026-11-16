<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import FocusLayout from '@/Layouts/FocusLayout.vue'

defineOptions({ layout: FocusLayout })

const props = defineProps({
    prefix: { type: String, default: 'store' },
    host: { type: String, default: 'porrop.com.ng' },
})

const user = computed(() => usePage().props.auth?.user)

const form = useForm({
    store_name: '',
    slug: '',
    whatsapp_number: '',
    name: '',
    email: '',
    password: '',
})

const slugTouched = ref(false)
const check = ref({ state: 'idle', reason: '' }) // idle | checking | ok | bad

const slugify = (s) =>
    s.toLowerCase().trim().replace(/['’]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40)

// Fill the link from the store name until the vendor edits the link themselves.
watch(() => form.store_name, (v) => {
    if (!slugTouched.value) form.slug = slugify(v)
})

let timer = null
let seq = 0
watch(() => form.slug, (slug) => {
    clearTimeout(timer)
    if (slug.length < 3) {
        check.value = { state: 'idle', reason: '' }
        return
    }
    check.value = { state: 'checking', reason: '' }
    const mine = ++seq
    timer = setTimeout(async () => {
        try {
            const res = await fetch(`/vendor/slug-check?slug=${encodeURIComponent(slug)}`, {
                headers: { Accept: 'application/json' },
            })
            const data = await res.json()
            if (mine !== seq) return // a newer keystroke is in flight
            check.value = data.available
                ? { state: 'ok', reason: '' }
                : { state: 'bad', reason: data.reason || 'Not available.' }
        } catch {
            if (mine === seq) check.value = { state: 'idle', reason: '' }
        }
    }, 350)
})

const submit = () => form.post('/vendor/register')

const input = 'mt-1 block w-full rounded-lg border-primary/20 bg-white shadow-sm focus:border-primary focus:ring-primary'
</script>

<template>
    <Head title="Open your Porrop store" />

    <div class="rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <h1 class="font-heading text-3xl font-semibold text-primary-dark">Open your store</h1>
        <p class="mt-2 text-sm text-neutral-text/70">Takes about a minute. No setup fee, no monthly fee.</p>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div>
                <label for="store_name" class="text-sm font-medium">Store name</label>
                <input id="store_name" v-model="form.store_name" type="text" autocomplete="organization" :class="input" placeholder="Ada's Thrift" />
                <p v-if="form.errors.store_name" class="mt-1 text-sm text-red-700">{{ form.errors.store_name }}</p>
            </div>

            <div>
                <label for="slug" class="text-sm font-medium">Your store link</label>
                <div class="mt-1 flex items-stretch overflow-hidden rounded-lg border border-primary/20 bg-white shadow-sm focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
                    <span class="flex items-center bg-secondary px-3 text-sm text-neutral-text/70">{{ host }}/{{ prefix }}/</span>
                    <input
                        id="slug"
                        v-model="form.slug"
                        type="text"
                        autocapitalize="none"
                        autocomplete="off"
                        class="block w-full border-0 focus:ring-0"
                        placeholder="adas-thrift"
                        @input="slugTouched = true"
                    />
                </div>
                <p class="mt-1 min-h-[1.25rem] text-sm" aria-live="polite">
                    <span v-if="check.state === 'checking'" class="text-neutral-text/60">Checking...</span>
                    <span v-else-if="check.state === 'ok'" class="text-primary">That link is yours to take.</span>
                    <span v-else-if="check.state === 'bad'" class="text-red-700">{{ check.reason }}</span>
                </p>
                <p v-if="form.errors.slug" class="text-sm text-red-700">{{ form.errors.slug }}</p>
            </div>

            <div>
                <label for="whatsapp" class="text-sm font-medium">WhatsApp number</label>
                <input id="whatsapp" v-model="form.whatsapp_number" type="tel" inputmode="tel" autocomplete="tel" :class="input" placeholder="0803 123 4567" />
                <p class="mt-1 text-xs text-neutral-text/60">Customers will be able to message you here.</p>
                <p v-if="form.errors.whatsapp_number" class="mt-1 text-sm text-red-700">{{ form.errors.whatsapp_number }}</p>
            </div>

            <template v-if="!user">
                <hr class="border-primary/10" />
                <p class="text-sm font-medium">Your login</p>

                <div>
                    <label for="name" class="text-sm font-medium">Your name</label>
                    <input id="name" v-model="form.name" type="text" autocomplete="name" :class="input" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-700">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="email" class="text-sm font-medium">Email</label>
                    <input id="email" v-model="form.email" type="email" autocomplete="email" :class="input" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-700">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label for="password" class="text-sm font-medium">Password</label>
                    <input id="password" v-model="form.password" type="password" autocomplete="new-password" :class="input" />
                    <p class="mt-1 text-xs text-neutral-text/60">At least 8 characters.</p>
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-700">{{ form.errors.password }}</p>
                </div>
            </template>

            <p class="text-xs text-neutral-text/70">
                By opening a store you agree to the
                <a href="/seller-terms" target="_blank" rel="noopener" class="font-medium text-primary underline">Seller Terms</a> and
                <a href="/privacy" target="_blank" rel="noopener" class="font-medium text-primary underline">Privacy Policy</a>.
            </p>
            <button
                type="submit"
                :disabled="form.processing || check.state === 'bad'"
                class="w-full rounded-xl bg-accent px-5 py-3 text-base font-semibold text-ink shadow-soft transition hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ form.processing ? 'Opening your store...' : 'Open my store' }}
            </button>

            <p v-if="!user" class="text-center text-sm text-neutral-text/70">
                Already have an account?
                <Link href="/login" class="font-medium text-primary underline">Log in</Link>
            </p>
        </form>
    </div>
</template>
