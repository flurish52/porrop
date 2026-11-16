<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Pages get `nav` from VendorAreaController: { name, dashboard, products, store }
const page = usePage()
const nav = computed(() => page.props.nav ?? {})
const flash = computed(() => page.props.flash ?? {})

const active = (href) => href && page.url.split('?')[0].startsWith(new URL(href, 'http://x').pathname)
const linkClass = (href, exact = false) => [
    'rounded-lg px-3 py-2 text-sm font-medium transition',
    (exact ? page.url.split('?')[0] === new URL(href || '/', 'http://x').pathname : active(href))
        ? 'bg-primary text-white'
        : 'text-primary-dark hover:bg-primary-light',
]
</script>

<template>
    <div class="min-h-screen bg-neutral-bg text-neutral-text">
        <header class="sticky top-0 z-30 border-b border-primary/10 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center gap-4 px-4 py-2 sm:gap-6 sm:px-6 sm:py-3">
                <Link href="/" class="flex-none font-heading text-2xl font-semibold text-primary">Porrop</Link>
                <nav class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto" aria-label="Store">
                    <Link :href="nav.dashboard || '#'" :class="linkClass(nav.dashboard, true)">Dashboard</Link>
                    <Link :href="nav.products || '#'" :class="linkClass(nav.products)">Products</Link>
                    <Link :href="nav.orders || '#'" :class="linkClass(nav.orders)">Orders</Link>
                    <Link :href="nav.payout || '#'" :class="linkClass(nav.payout)">Payouts</Link>
                    <a :href="nav.store || '#'" target="_blank" rel="noopener" class="rounded-lg px-3 py-2 text-sm font-medium text-primary-dark hover:bg-primary-light">
                        View store
                    </a>
                </nav>
                <span class="hidden max-w-[12rem] truncate text-sm text-neutral-text/60 sm:block">{{ nav.name }}</span>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex-none rounded-lg px-3 py-2 text-sm font-medium text-primary-dark transition hover:bg-primary-light"
                >
                    Log out
                </Link>
            </div>
        </header>

        <div v-if="flash.success || flash.error" class="mx-auto mt-4 max-w-5xl px-4 sm:px-6">
            <p v-if="flash.success" class="rounded-lg bg-primary-light px-4 py-3 text-sm text-primary-dark">{{ flash.success }}</p>
            <p v-if="flash.error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{{ flash.error }}</p>
        </div>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10">
            <slot />
        </main>
    </div>
</template>
