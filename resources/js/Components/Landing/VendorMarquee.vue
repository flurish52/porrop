<script setup>
/**
 * Thin scrolling strip of vendor benefits. Sits above or below the hero.
 * Every line is something the product is built to do (see SETUP.md / config/porrop.php):
 * keep the numbers in sync with config('porrop.commission') if pricing changes.
 *
 * Seamless loop: two identical tracks side by side, the strip slides exactly one track
 * (-50%) and snaps back, so the jump is invisible.
 */
const items = [
    { icon: 'link', label: 'Your own store link in minutes' },
    { icon: 'card', label: 'Customers pay online: no more “send account number”' },
    { icon: 'percent', label: '10% per sale, never more than ₦5,000' },
    { icon: 'check', label: 'No setup fee. No monthly fee.' },
    { icon: 'bank', label: 'Paid out to your bank account' },
    { icon: 'inbox', label: 'Every order in one dashboard' },
    { icon: 'chat', label: 'Built for WhatsApp sellers' },
]

const icons = {
    link: 'M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7',
    card: 'M3 6h18v12H3zM3 10h18M7 15h3',
    percent: 'M19 5L5 19M7.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM16.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
    check: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM8 12l3 3 5-5',
    bank: 'M3 10l9-6 9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 20h18',
    inbox: 'M3 4h18v16H3zM3 9h18M9 9v11',
    chat: 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z',
}

// Same list twice. The second copy is hidden from screen readers.
const tracks = ['a', 'b']
</script>

<template>
    <div class="overflow-hidden bg-primary-dark" role="region" aria-label="Why vendors use Porrop">
        <div class="marquee flex w-max items-center py-2.5 text-xs font-medium text-white/95 sm:text-sm">
            <div
                v-for="track in tracks"
                :key="track"
                class="flex shrink-0 items-center"
                :aria-hidden="track === 'b' ? 'true' : undefined"
            >
                <div v-for="item in items" :key="`${track}-${item.label}`" class="flex shrink-0 items-center">
                    <span class="flex items-center gap-2 px-4 sm:px-5">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0 text-accent">
                            <path :d="icons[item.icon]" />
                        </svg>
                        <span class="whitespace-nowrap">{{ item.label }}</span>
                    </span>
                    <!-- divider -->
                    <span class="h-1 w-1 rounded-full bg-accent" aria-hidden="true" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.marquee {
    animation: marquee-scroll 38s linear infinite;
}

.marquee:hover {
    animation-play-state: paused;
}

@keyframes marquee-scroll {
    from { transform: translateX(0); }
    /* Both tracks are identical, so moving exactly one track's width lands on the same frame. */
    to { transform: translateX(-50%); }
}

@media (prefers-reduced-motion: reduce) {
    .marquee { animation: none; }
}
</style>
