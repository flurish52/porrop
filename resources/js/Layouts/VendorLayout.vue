<script setup>
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Pages get `nav` from VendorAreaController: { name, dashboard, products, orders, payout, profile, store }
const page = usePage()
const nav = computed(() => page.props.nav ?? {})
const flash = computed(() => page.props.flash ?? {})

const path = computed(() => page.url.split('?')[0])
const pathOf = (href) => new URL(href || '/', 'http://x').pathname
const isActive = (href, exact = false) =>
    !!href && (exact ? path.value === pathOf(href) : path.value.startsWith(pathOf(href)))

// Desktop top nav (unchanged look)
const linkClass = (href, exact = false) => [
    'rounded-lg px-3 py-2 text-sm font-medium transition',
    isActive(href, exact) ? 'bg-primary text-white' : 'text-primary-dark hover:bg-primary-light',
]

// Mobile bottom tabs
const tabs = computed(() => [
    {
        label: 'Dashboard',
        href: nav.value.dashboard,
        exact: true,
        d: 'm2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
    },
    {
        label: 'Products',
        href: nav.value.products,
        exact: false,
        d: 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
    },
    {
        label: 'Orders',
        href: nav.value.orders,
        exact: false,
        d: 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
    },
    {
        label: 'Payouts',
        href: nav.value.payout,
        exact: false,
        d: 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z',
    },
])
const moreIcon =
    'M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z'

const moreOpen = ref(false)
const moreActive = computed(() => isActive(nav.value.profile))

// Close the "More" menu whenever the page changes
watch(() => page.url, () => { moreOpen.value = false })
</script>

<template>
    <div class="min-h-screen bg-neutral-bg text-neutral-text" @keydown.esc="moreOpen = false">
        <header class="sticky top-0 z-30 border-b border-primary/10 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center gap-4 px-4 py-2 sm:gap-6 sm:px-6 sm:py-3">
                <Link href="/" class="flex-none font-heading text-2xl font-semibold text-primary">Porrop</Link>

                <!-- Desktop nav -->
                <nav class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto sm:flex" aria-label="Store">
                    <Link :href="nav.dashboard || '#'" :class="linkClass(nav.dashboard, true)">Dashboard</Link>
                    <Link :href="nav.products || '#'" :class="linkClass(nav.products)">Products</Link>
                    <Link :href="nav.orders || '#'" :class="linkClass(nav.orders)">Orders</Link>
                    <Link :href="nav.payout || '#'" :class="linkClass(nav.payout)">Payouts</Link>
                    <Link :href="nav.profile || '#'" :class="linkClass(nav.profile)">Store profile</Link>
                    <a :href="nav.store || '#'" target="_blank" rel="noopener" class="rounded-lg px-3 py-2 text-sm font-medium text-primary-dark hover:bg-primary-light">
                        View store
                    </a>
                </nav>

                <!-- Store name: right-aligned on mobile, after the nav on desktop -->
                <span class="min-w-0 flex-1 truncate text-right text-sm text-neutral-text/60 sm:max-w-[12rem] sm:flex-none">{{ nav.name }}</span>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="hidden flex-none rounded-lg px-3 py-2 text-sm font-medium text-primary-dark transition hover:bg-primary-light sm:block"
                >
                    Log out
                </Link>
            </div>
        </header>

        <div v-if="flash.success || flash.error" class="mx-auto mt-4 max-w-5xl px-4 sm:px-6">
            <p v-if="flash.success" class="rounded-lg bg-primary-light px-4 py-3 text-sm text-primary-dark">{{ flash.success }}</p>
            <p v-if="flash.error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{{ flash.error }}</p>
        </div>

        <!-- Extra bottom padding on mobile so the tab bar never covers content -->
        <main class="mx-auto max-w-5xl px-4 pb-28 pt-8 sm:px-6 sm:py-10">
            <slot />
        </main>

        <!-- Mobile: tap-away backdrop + "More" menu -->
        <button
            v-if="moreOpen"
            type="button"
            class="fixed inset-0 z-30 bg-black/30 sm:hidden"
            aria-label="Close menu"
            @click="moreOpen = false"
        />
        <div
            v-if="moreOpen"
            id="mobile-more-menu"
            class="fixed bottom-[calc(4rem+env(safe-area-inset-bottom))] right-3 z-40 w-56 overflow-hidden rounded-2xl border border-primary/10 bg-white py-1 shadow-soft sm:hidden"
        >
            <Link
                :href="nav.profile || '#'"
                class="block px-4 py-3 text-sm font-medium"
                :class="moreActive ? 'bg-primary-light text-primary' : 'text-primary-dark hover:bg-primary-light'"
            >
                Store profile
            </Link>
            <a
                :href="nav.store || '#'"
                target="_blank"
                rel="noopener"
                class="block px-4 py-3 text-sm font-medium text-primary-dark hover:bg-primary-light"
                @click="moreOpen = false"
            >
                View store
            </a>
            <div class="my-1 border-t border-primary/10" />
            <Link
                href="/logout"
                method="post"
                as="button"
                class="block w-full px-4 py-3 text-left text-sm font-medium text-red-700 hover:bg-red-50"
            >
                Log out
            </Link>
        </div>

        <!-- Mobile bottom tab bar -->
        <nav
            class="fixed inset-x-0 bottom-0 z-40 border-t border-primary/10 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur sm:hidden"
            aria-label="Store mobile"
        >
            <ul class="mx-auto flex h-16 max-w-md items-stretch">
                <li v-for="tab in tabs" :key="tab.label" class="flex-1">
                    <Link
                        :href="tab.href || '#'"
                        class="flex h-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium transition"
                        :class="isActive(tab.href, tab.exact) ? 'text-primary' : 'text-neutral-text/60'"
                        :aria-current="isActive(tab.href, tab.exact) ? 'page' : undefined"
                    >
                        <span
                            class="flex h-7 w-12 items-center justify-center rounded-full transition"
                            :class="isActive(tab.href, tab.exact) ? 'bg-primary-light' : ''"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" :stroke-width="isActive(tab.href, tab.exact) ? 2 : 1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="tab.d" />
                            </svg>
                        </span>
                        {{ tab.label }}
                    </Link>
                </li>

                <li class="flex-1">
                    <button
                        type="button"
                        class="flex h-full w-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium transition"
                        :class="moreOpen || moreActive ? 'text-primary' : 'text-neutral-text/60'"
                        aria-haspopup="true"
                        aria-controls="mobile-more-menu"
                        :aria-expanded="moreOpen"
                        @click="moreOpen = !moreOpen"
                    >
                        <span
                            class="flex h-7 w-12 items-center justify-center rounded-full transition"
                            :class="moreOpen || moreActive ? 'bg-primary-light' : ''"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="moreIcon" />
                            </svg>
                        </span>
                        More
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</template>
