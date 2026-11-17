<script setup>
import {computed, ref} from 'vue'
import { Link, usePage} from '@inertiajs/vue3'
import CartDrawer from '@/Components/Store/CartDrawer.vue'
import CartBar from '@/Components/Store/CartBar.vue'

const page = usePage()
const shop = computed(() => page.props.shop ?? {})
const flash = computed(() => page.props.flash ?? {})
const emptyCart = { count: 0, subtotal: 0, lines: [], quantities: {} }
const cart = computed(() => shop.value.cart ?? emptyCart)
const drawer = ref(false)
const onCartPage = computed(() => ['Store/Cart', 'Store/Checkout'].includes(page.component))
</script>

<template>
    <div class="min-h-screen bg-neutral-bg text-neutral-text">
        <header class="sticky top-0 z-30 bg-primary-dark text-white shadow-sm">
            <div class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-4 sm:px-6">
                <Link :href="shop.url || '/'" class="flex min-w-0 flex-1 items-center gap-3">
                    <img v-if="shop.logo" :src="shop.logo" alt="" class="h-9 w-9 flex-none rounded-full object-cover" />
                    <span class="truncate font-heading text-xl font-semibold">{{ shop.name }}</span>
                </Link>
                <Link v-if="onCartPage" :href="shop.cart_url || '#'" class="flex-none rounded-lg border border-white/30 px-3 py-2 text-sm font-medium hover:bg-white/10">
                    Cart<span v-if="cart.count" class="ml-1.5 rounded-full bg-accent px-2 py-0.5 text-xs font-semibold text-ink">{{ cart.count }}</span>
                </Link>
                <button v-else type="button" class="flex-none rounded-lg border border-white/30 px-3 py-2 text-sm font-medium hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" @click="drawer = true">
                    Cart<span v-if="cart.count" class="ml-1.5 rounded-full bg-accent px-2 py-0.5 text-xs font-semibold text-ink">{{ cart.count }}</span>
                </button>
            </div>
        </header>

        <div v-if="flash.success || flash.error" class="mx-auto mt-4 max-w-5xl px-4 sm:px-6">
            <p v-if="flash.success" class="rounded-lg bg-primary-light px-4 py-3 text-sm text-primary-dark" role="status">{{ flash.success }}</p>
            <p v-if="flash.error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ flash.error }}</p>
        </div>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6"
              :class="!onCartPage && cart.count ? 'pb-24' : ''">
            <slot />
        </main>

        <template v-if="!onCartPage">
            <CartBar :cart="cart" @open="drawer = true" />
            <CartDrawer :open="drawer" :cart="cart" @close="drawer = false" />
        </template>
        <footer class="py-6 text-center text-xs text-neutral-text/60">
            <p>
                <a href="/terms" class="underline">Terms</a> &middot;
                <a href="/privacy" class="underline">Privacy</a> &middot;
                <a href="/refunds" class="underline">Refunds</a>
            </p>
            Powered by <a href="/" class="underline">Porrop</a>
            <span aria-hidden="true"> &middot; </span>
            <a href="/orders/find" class="underline">Find my order</a>
        </footer>
    </div>
</template>
