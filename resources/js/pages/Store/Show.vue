<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import CartDrawer from '@/Components/Store/CartDrawer.vue'
import CartBar from '@/Components/Store/CartBar.vue'

const props = defineProps({
    store: { type: Object, required: true },
    products: { type: Array, default: () => [] },
})

const flash = computed(() => usePage().props.flash ?? {})
const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`

const cart = computed(() => props.store.cart ?? { count: 0, subtotal: 0, lines: [], quantities: {} })
const drawer = ref(false)

const scrolled = ref(false)
const readMore = ref(false)
const onScroll = () => { scrolled.value = window.scrollY > 140 }
onMounted(() => { onScroll(); window.addEventListener('scroll', onScroll, { passive: true }) })
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))

const inCart = (p) => cart.value.quantities?.[p.quick_add_variant_id] ?? 0

const addToCart = (p) =>
    router.post(props.store.add_url, { variant_id: p.quick_add_variant_id, quantity: 1 }, { preserveScroll: true })

const chatHref = computed(() =>
    props.store.whatsapp_number
        ? `https://wa.me/${props.store.whatsapp_number}?text=${encodeURIComponent(`Hello ${props.store.name}, I saw your store on Porrop.`)}`
        : '',
)

/* ---- search ---- */
const query = ref('')

// Lowercase and strip accents so "cafe" finds "Café"
const normalize = (s) =>
    String(s ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim()

// Every word typed must appear in the product name, in any order ("blue shirt" finds "Shirt, blue")
const filtered = computed(() => {
    const words = normalize(query.value).split(/\s+/).filter(Boolean)
    if (!words.length) return props.products
    return props.products.filter((p) => {
        const name = normalize(p.name)
        return words.every((w) => name.includes(w))
    })
})

const searching = computed(() => normalize(query.value) !== '')
const clearSearch = () => { query.value = '' }
</script>

<template>
    <Head :title="store.name" />

    <div class="min-h-screen bg-neutral-bg text-neutral-text">

        <div class="fixed inset-x-0 top-0 z-30 bg-primary-dark text-white shadow-md transition-transform duration-200" :class="scrolled ? 'translate-y-0' : '-translate-y-full'" :inert="!scrolled">
            <div class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-3 sm:px-6">
                <img v-if="store.logo" :src="store.logo" alt="" class="h-8 w-8 flex-none rounded-full object-cover" />
                <span class="min-w-0 flex-1 truncate font-heading text-lg font-semibold">{{ store.name }}</span>
                <button type="button" class="inline-flex flex-none items-center gap-2 rounded-lg bg-accent px-3.5 py-1.5 text-sm font-bold text-ink shadow-md shadow-black/25 transition hover:bg-accent-light active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" @click="drawer = true">
                    Cart<span v-if="cart.count" class="rounded-full bg-primary-dark px-2 py-0.5 text-xs font-bold text-white">{{ cart.count }}</span>
                </button>
            </div>
        </div>
        <header class="bg-primary-dark text-white">
            <img v-if="store.banner" :src="store.banner" alt="" class="h-28 w-full object-cover sm:h-40" />
            <div class="mx-auto max-w-5xl px-4 py-5 sm:px-6 sm:py-7">
                <div class="flex items-center gap-3 sm:gap-4">
                    <img v-if="store.logo" :src="store.logo" :alt="`${store.name} logo`" class="h-12 w-12 flex-none rounded-full object-cover sm:h-14 sm:w-14" />
                    <h1 class="min-w-0 flex-1 break-words font-heading text-2xl font-semibold leading-tight sm:text-3xl">{{ store.name }}</h1>

                    <a v-if="chatHref" :href="chatHref" target="_blank" rel="noopener"
                       class="inline-flex flex-none items-center gap-1.5 rounded-lg bg-whatsapp px-3.5 py-2 text-sm font-bold text-ink shadow-md shadow-black/25 ring-1 ring-white/20 transition hover:brightness-110 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.7 7.1L3 21l1.9-6.2A8 8 0 1 1 21 12Z" /></svg>
                        Chat
                    </a>

                    <button type="button"
                            class="hidden flex-none items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-bold text-ink shadow-md shadow-black/25 transition hover:bg-accent-light active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-white sm:inline-flex"
                            @click="drawer = true">
                        Cart<span v-if="cart.count" class="rounded-full bg-primary-dark px-2 py-0.5 text-xs font-bold text-white">{{ cart.count }}</span>
                    </button>
                </div>
                <p v-if="store.tagline" class="mt-2 text-sm font-medium text-accent">{{ store.tagline }}</p>

                <div v-if="store.description" class="mt-3 max-w-2xl">
                    <p class="text-sm leading-relaxed text-white/80" :class="readMore ? '' : 'line-clamp-3 sm:line-clamp-none'">{{ store.description }}</p>
                    <button v-if="store.description.length > 140" type="button" class="mt-1 text-xs font-semibold text-accent underline focus:outline-none focus-visible:ring-2 focus-visible:ring-white sm:hidden" @click="readMore = !readMore">
                        {{ readMore ? 'Show less' : 'Read more' }}
                    </button>
                </div>

                <button type="button"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 py-3 text-base font-bold text-ink shadow-lg shadow-black/30 transition hover:bg-accent-light active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary-dark sm:hidden"
                        @click="drawer = true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z" /><path d="M9 8V6.5a3 3 0 0 1 6 0V8" /></svg>
                    View cart<span v-if="cart.count" class="rounded-full bg-primary-dark px-2 py-0.5 text-xs font-bold text-white">{{ cart.count }}</span>
                </button>
            </div>
        </header>

        <div v-if="flash.success || flash.error" class="mx-auto mt-4 max-w-5xl px-4 sm:px-6">
            <p v-if="flash.success" class="rounded-lg bg-primary-light px-4 py-3 text-sm text-primary-dark">{{ flash.success }}</p>
            <p v-if="flash.error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{{ flash.error }}</p>
        </div>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6" :class="cart.count ? 'pb-24' : ''">
            <p v-if="!products.length" class="rounded-2xl bg-white p-8 text-center text-sm text-neutral-text/70 shadow-soft">
                No products yet. Check back soon.
            </p>

            <template v-else>
                <!-- Search -->
                <form role="search" class="mb-5" @submit.prevent>
                    <label for="product-search" class="sr-only">Search products in {{ store.name }}</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-neutral-text/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
                        </svg>
                        <input
                            id="product-search"
                            v-model="query"
                            type="search"
                            inputmode="search"
                            enterkeyhint="search"
                            autocomplete="off"
                            autocapitalize="none"
                            placeholder="Search this store"
                            class="block w-full rounded-xl border-primary/20 bg-white py-3 pl-11 pr-11 text-base shadow-sm placeholder:text-neutral-text/50 focus:border-primary focus:ring-primary [&::-webkit-search-cancel-button]:hidden"
                            @keydown.esc="clearSearch"
                        />
                        <button
                            v-if="query"
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-neutral-text/50 hover:text-primary focus:outline-none focus-visible:text-primary"
                            aria-label="Clear search"
                            @click="clearSearch"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                        </button>
                    </div>
                    <p v-if="searching" class="mt-2 text-sm text-neutral-text/70" aria-live="polite">
                        {{ filtered.length }} {{ filtered.length === 1 ? 'product' : 'products' }} found
                    </p>
                </form>

                <div v-if="!filtered.length" class="rounded-2xl bg-white p-8 text-center shadow-soft">
                    <p class="text-sm text-neutral-text/70">Nothing matches "{{ query.trim() }}".</p>
                    <button type="button" class="mt-3 rounded-lg border border-primary/25 px-4 py-2 text-sm font-semibold text-primary-dark hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" @click="clearSearch">
                        Show all products
                    </button>
                    <p v-if="chatHref" class="mt-4 text-sm text-neutral-text/70">
                        Can't find it?
                        <a :href="chatHref" target="_blank" rel="noopener" class="font-medium text-primary underline">Ask the seller on WhatsApp</a>
                    </p>
                </div>

                <ul v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <li v-for="p in filtered" :key="p.id" class="flex flex-col overflow-hidden rounded-xl bg-white shadow-soft">
                        <a :href="p.url" class="block flex-1">
                            <div class="aspect-square bg-secondary">
                                <img v-if="p.image" :src="p.image" :alt="p.name" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                            <div class="p-3 pb-2">
                                <p class="truncate text-sm font-medium">{{ p.name }}</p>
                                <p class="mt-1 text-sm font-semibold text-primary">{{ p.price_from !== null ? naira(p.price_from) : '' }}</p>
                            </div>
                        </a>
                        <div class="px-3 pb-3">
                            <p v-if="!p.in_stock" class="text-xs font-medium text-red-700">Sold out</p>
                            <button v-else-if="p.quick_add_variant_id" type="button" class="w-full rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-ink hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" @click="addToCart(p)">
                                {{ inCart(p) ? 'Add one more' : 'Add to cart' }}
                            </button>
                            <a v-else :href="p.url" class="block w-full rounded-lg border border-primary/25 px-3 py-2 text-center text-sm font-semibold text-primary-dark hover:bg-primary-light">Choose options</a>
                            <p v-if="p.quick_add_variant_id && inCart(p)" class="mt-1 text-center text-xs font-medium text-primary">{{ inCart(p) }} in your cart</p>
                        </div>
                    </li>
                </ul>
            </template>
        </main>

        <CartBar :cart="cart" @open="drawer = true" />
        <CartDrawer :open="drawer" :cart="cart" @close="drawer = false" />

        <footer class="py-6 text-center text-xs text-neutral-text/60">
            <p>
                <a href="/terms" class="underline">Terms</a> &middot;
                <a href="/privacy" class="underline">Privacy</a> &middot;
                <a href="/refunds" class="underline">Refunds</a>
            </p>
            <p class="mt-2">Powered by <a href="/" class="underline">Porrop</a></p>
        </footer>
    </div>
</template>
