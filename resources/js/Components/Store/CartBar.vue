<script setup>
// Sticky bar at the bottom of the screen while the cart has items.
defineProps({ cart: { type: Object, required: true } })
defineEmits(['open'])

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`
</script>

<template>
    <div v-if="cart.count > 0" class="fixed inset-x-0 bottom-0 z-40 border-t border-primary/10 bg-white/95 px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] backdrop-blur" aria-live="polite">
        <div class="mx-auto flex max-w-5xl items-center gap-3">
            <p class="min-w-0 flex-1 truncate text-sm">
                <span class="font-semibold">{{ cart.count }} {{ cart.count === 1 ? 'item' : 'items' }}</span>
                <span class="text-neutral-text/60"> &middot; </span>
                <span class="font-semibold text-primary">{{ naira(cart.subtotal) }}</span>
            </p>
            <button type="button" class="flex-none rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-ink shadow-soft hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2" @click="$emit('open')">View cart</button>
        </div>
    </div>
</template>
