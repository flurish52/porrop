<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const flash = computed(() => page.props.flash ?? {})
const links = [
    { label: 'Stores', href: '/admin/stores' },
    { label: 'Payouts', href: '/admin/payouts' },
]
const isActive = (href) => (page.url || '').startsWith(href)
</script>

<template>
    <div class="min-h-screen bg-neutral-bg text-neutral-text">
        <header class="bg-primary-dark text-white">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 sm:px-6">
                <span class="whitespace-nowrap font-heading text-xl font-semibold">Porrop admin</span>
                <nav class="flex flex-1 gap-1 text-sm" aria-label="Admin">
                    <Link v-for="l in links" :key="l.href" :href="l.href" class="rounded-lg px-3 py-2 font-medium" :class="isActive(l.href) ? 'bg-white/10' : 'text-white/70 hover:text-white'">{{ l.label }}</Link>
                </nav>
                <Link href="/" class="whitespace-nowrap text-sm text-white/70 hover:text-white">Back to site</Link>
            </div>
        </header>

        <div v-if="flash.success || flash.error" class="mx-auto mt-4 max-w-5xl px-4 sm:px-6">
            <p v-if="flash.success" class="rounded-lg bg-primary-light px-4 py-3 text-sm text-primary-dark" role="status">{{ flash.success }}</p>
            <p v-if="flash.error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ flash.error }}</p>
        </div>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            <slot />
        </main>
    </div>
</template>
