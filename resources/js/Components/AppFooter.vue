<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const year = new Date().getFullYear()

const whatsappHref = computed(() => {
    const n = page.props.supportWhatsapp
    return n ? `https://wa.me/${n}?text=${encodeURIComponent('Hello Porrop, I need help.')}` : ''
})
</script>

<template>
    <footer class="bg-primary-dark text-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <p class="font-heading text-2xl font-semibold">Porrop</p>
                <p class="mt-1 max-w-xs text-sm text-white/70">
                    Open your store, share one link, get paid. Built for Nigerian sellers.
                </p>
            </div>

            <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <Link href="/vendor/register" class="text-white/80 hover:text-white">Open your store</Link>
                <Link href="/login" class="text-white/80 hover:text-white">Log in</Link>

                <Link href="/terms" class="text-white/80 hover:text-white">Terms</Link>
                <Link href="/seller-terms" class="text-white/80 hover:text-white">Seller terms</Link>
                <Link href="/privacy" class="text-white/80 hover:text-white">Privacy</Link>
                <Link href="/refunds" class="text-white/80 hover:text-white">Refunds</Link>

                <a v-if="whatsappHref" :href="whatsappHref" target="_blank" rel="noopener" class="text-white/80 hover:text-white">
                    Chat with us
                </a>
            </nav>
        </div>
        <div class="border-t border-white/10 py-4 text-center text-xs text-white/60">
            &copy; {{ year }} Porrop. All rights reserved.
        </div>
    </footer>
</template>
