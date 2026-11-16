<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import FocusLayout from '@/Layouts/FocusLayout.vue'

defineOptions({ layout: FocusLayout })

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
})

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const showPassword = ref(false)

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}

const input = 'mt-1 block w-full rounded-lg border-primary/20 bg-white shadow-sm focus:border-primary focus:ring-primary'
</script>

<template>
    <Head title="Log in" />

    <div class="rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <h1 class="font-heading text-3xl font-semibold text-primary-dark">Welcome back</h1>
        <p class="mt-2 text-sm text-neutral-text/70">Log in to manage your store, products and orders.</p>

        <p v-if="status" class="mt-4 rounded-lg bg-primary-light px-4 py-3 text-sm text-primary-dark" role="status">
            {{ status }}
        </p>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div>
                <label for="email" class="text-sm font-medium">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    :class="input"
                    placeholder="you@example.com"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-700">{{ form.errors.email }}</p>
            </div>

            <div>
                <div class="flex items-baseline justify-between">
                    <label for="password" class="text-sm font-medium">Password</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-medium text-primary underline"
                    >
                        Forgot password?
                    </Link>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        :class="[input, 'pr-12']"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-neutral-text/60 hover:text-primary focus:outline-none focus-visible:text-primary"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        :aria-pressed="showPassword"
                        @click="showPassword = !showPassword"
                    >
                        <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3.01 9.96 7.18.07.21.07.43 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3.01-9.96-7.18Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 1.93 12c1.29 4.34 5.31 7.5 10.07 7.5 1.99 0 3.84-.55 5.42-1.51M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.76 0 8.77 3.16 10.07 7.5a10.52 10.52 0 0 1-4.29 5.77M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 0-4.24-4.24m4.24 4.24L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-700">{{ form.errors.password }}</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-neutral-text/80">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    name="remember"
                    class="rounded border-primary/30 text-primary focus:ring-primary"
                />
                Keep me logged in on this device
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-accent px-5 py-3 text-base font-semibold text-ink shadow-soft transition hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ form.processing ? 'Logging in...' : 'Log in' }}
            </button>

            <p class="text-xs text-neutral-text/70">
                By logging in you agree to the
                <a href="/terms" target="_blank" rel="noopener" class="font-medium text-primary underline">Terms of Use</a> and
                <a href="/privacy" target="_blank" rel="noopener" class="font-medium text-primary underline">Privacy Policy</a>.
            </p>

            <p class="text-center text-sm text-neutral-text/70">
                New to Porrop?
                <Link href="/vendor/register" class="font-medium text-primary underline">Open your store for free</Link>
            </p>
        </form>
    </div>
</template>
