<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

// cart = { count, subtotal, lines: [{variant_id,name,variant_name,price,quantity,stock,total,image,product_url,update_url,remove_url}], cart_url, checkout_url }
const props = defineProps({
    open: { type: Boolean, default: false },
    cart: { type: Object, required: true },
})
const emit = defineEmits(['close'])

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`
const closeBtn = ref(null)

const setQty = (line, qty) =>
    router.patch(line.update_url, { quantity: qty }, { preserveScroll: true, preserveState: true })
const remove = (line) =>
    router.delete(line.remove_url, { preserveScroll: true, preserveState: true })

const onKey = (e) => { if (e.key === 'Escape') emit('close') }

watch(() => props.open, async (isOpen) => {
    try { document.body.style.overflow = isOpen ? 'hidden' : '' } catch {}
    if (isOpen) {
        window.addEventListener('keydown', onKey)
        await nextTick()
        closeBtn.value?.focus()
    } else {
        window.removeEventListener('keydown', onKey)
    }
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey)
    try { document.body.style.overflow = '' } catch {}
})
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-ink/50" @click="emit('close')"></div>

        <aside class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl" role="dialog" aria-modal="true" aria-label="Your cart">
            <div class="flex items-center justify-between border-b border-primary/10 px-4 py-3">
                <h2 class="font-heading text-lg font-semibold text-primary-dark">Your cart<span v-if="cart.count" class="ml-1 text-sm font-normal text-neutral-text/60">({{ cart.count }})</span></h2>
                <button ref="closeBtn" type="button" class="rounded-lg px-3 py-1.5 text-sm font-medium text-primary hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" @click="emit('close')">Close</button>
            </div>

            <p v-if="!cart.lines.length" class="px-4 py-10 text-center text-sm text-neutral-text/70">Your cart is empty. Keep shopping.</p>

            <ul v-else class="flex-1 divide-y divide-primary/10 overflow-y-auto px-4">
                <li v-for="l in cart.lines" :key="l.variant_id" class="flex gap-3 py-4">
                    <div class="h-16 w-16 flex-none overflow-hidden rounded-lg bg-secondary">
                        <img v-if="l.image" :src="l.image" alt="" class="h-full w-full object-cover" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <a :href="l.product_url" class="block truncate text-sm font-medium hover:underline">{{ l.name }}</a>
                        <p v-if="l.variant_name" class="truncate text-xs text-neutral-text/60">{{ l.variant_name }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-primary">{{ naira(l.total) }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <button type="button" class="h-8 w-8 rounded-lg border border-primary/25 text-base leading-none hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" :aria-label="`Fewer ${l.name}`" @click="setQty(l, l.quantity - 1)">&minus;</button>
                            <span class="w-6 text-center text-sm" aria-live="polite">{{ l.quantity }}</span>
                            <button type="button" class="h-8 w-8 rounded-lg border border-primary/25 text-base leading-none hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-40" :disabled="l.quantity >= l.stock" :aria-label="`More ${l.name}`" @click="setQty(l, l.quantity + 1)">+</button>
                            <button type="button" class="ml-auto text-xs font-medium text-red-700 underline" @click="remove(l)">Remove</button>
                        </div>
                    </div>
                </li>
            </ul>

            <div v-if="cart.lines.length" class="space-y-2 border-t border-primary/10 px-4 py-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-text/70">Subtotal</span>
                    <span class="text-base font-semibold">{{ naira(cart.subtotal) }}</span>
                </div>
                <a :href="cart.checkout_url" class="block w-full rounded-xl bg-accent px-5 py-3 text-center text-base font-semibold text-ink shadow-soft hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">Checkout</a>
                <button type="button" class="block w-full text-center text-sm font-medium text-primary underline" @click="emit('close')">Keep shopping</button>
            </div>
        </aside>
    </div>
</template>
