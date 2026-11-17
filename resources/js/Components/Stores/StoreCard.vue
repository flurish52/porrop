<script setup>
import { computed } from 'vue'

const props = defineProps({
    store: { type: Object, required: true },
    // compact = used on the home page strip: smaller, three photos
    compact: { type: Boolean, default: false },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`
const initial = computed(() => (props.store.name || 'S').trim().charAt(0).toUpperCase())
const shown = computed(() => props.store.products.slice(0, props.compact ? 3 : 4))
</script>

<template>
    <article class="flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-primary/5">
        <div class="flex items-center gap-3 p-4">
            <img v-if="store.logo" :src="store.logo" alt="" class="h-12 w-12 flex-none rounded-full object-cover" loading="lazy" />
            <span v-else class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-primary font-heading text-xl font-semibold text-accent" aria-hidden="true">{{ initial }}</span>
            <div class="min-w-0">
                <h3 class="truncate font-heading text-lg font-semibold text-primary-dark">{{ store.name }}</h3>
                <p v-if="store.tagline" class="line-clamp-2 text-sm text-neutral-text/70">{{ store.tagline }}</p>
            </div>
        </div>

        <ul class="grid gap-1.5 px-4" :class="compact ? 'grid-cols-3' : 'grid-cols-4'">
            <li v-for="p in shown" :key="p.id">
                <a :href="p.url" class="group relative block aspect-square overflow-hidden rounded-lg bg-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <img v-if="p.image" :src="p.image" :alt="p.name" class="h-full w-full object-cover transition duration-200 group-hover:scale-105" loading="lazy" />
                    <span v-if="p.price_from !== null" class="absolute bottom-1 left-1 rounded bg-white/90 px-1.5 py-0.5 text-[11px] font-semibold text-ink">{{ naira(p.price_from) }}</span>
                    <span class="sr-only">{{ p.name }}</span>
                </a>
            </li>
        </ul>

        <div class="mt-auto p-4 pt-4">
            <a :href="store.url" class="block w-full rounded-xl bg-accent px-4 py-2.5 text-center text-sm font-bold text-ink shadow-soft transition hover:bg-accent-light active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                Visit store
                <span v-if="store.product_count > shown.length" class="font-medium text-ink/70"> &middot; {{ store.product_count }} items</span>
            </a>
        </div>
    </article>
</template>
