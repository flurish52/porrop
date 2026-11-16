<script setup>
/**
 * "Why sellers choose Porrop": three boxes. Replaces the pharmacy "Why Obudu Trusts Us".
 * Each box answers one worry a WhatsApp seller really has:
 *   pride   -> "I look like a real business"
 *   relief  -> "I stop chasing payments"
 *   control -> "I can see everything"
 * Keeps the fade-up-on-scroll reveal (respects reduced motion).
 */
import { ref, onMounted, onUnmounted } from 'vue'

defineProps({
    heading: { type: String, default: 'Why sellers choose Porrop' },
    lead: { type: String, default: 'Built for people who sell on WhatsApp today and want it to feel less like hard work.' },
    items: {
        type: Array,
        default: () => [
            {
                icon: 'store',
                title: 'Your name on a real store',
                text: 'Your own link, your logo and your prices. Customers see a proper shop, not a screenshot.',
            },
            {
                icon: 'card',
                title: 'No more chasing payments',
                text: 'Customers pay online when they order. No more “send account number” and no more asking who paid.',
            },
            {
                icon: 'dashboard',
                title: 'Everything in one place',
                text: 'Every order, every payment and what you keep on each sale, all in your dashboard.',
            },
        ],
    },
})

const icons = {
    store: 'M3 9l1.5-5h15L21 9M3 9h18M5 9v11h14V9M9 20v-6h6v6',
    card: 'M3 6h18v12H3zM3 10h18M7 15h3',
    dashboard: 'M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z',
}

const root = ref(null)
const visible = ref(false)
let observer = null

onMounted(() => {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (reduced || !('IntersectionObserver' in window)) {
        visible.value = true
        return
    }

    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                visible.value = true
                observer.disconnect()
            }
        },
        { threshold: 0.2 }
    )
    if (root.value) observer.observe(root.value)
})

onUnmounted(() => observer?.disconnect())
</script>

<template>
    <section ref="root" class="relative py-16 md:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-heading text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ heading }}</h2>
                <p class="mt-3 text-base leading-relaxed text-ink/65">{{ lead }}</p>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    v-for="(item, index) in items"
                    :key="item.title"
                    class="why-item flex items-start gap-3.5 rounded-2xl border border-ink/10 bg-white p-5 shadow-soft"
                    :class="{ 'why-item--visible': visible }"
                    :style="{ transitionDelay: `${index * 90}ms` }"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-light text-primary" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path :d="icons[item.icon]" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-heading text-base font-semibold leading-tight text-ink">{{ item.title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink/65">{{ item.text }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.why-item {
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.5s ease-out, transform 0.5s ease-out;
}
.why-item--visible {
    opacity: 1;
    transform: translateY(0);
}
@media (prefers-reduced-motion: reduce) {
    .why-item { opacity: 1; transform: none; transition: none; }
}
</style>
