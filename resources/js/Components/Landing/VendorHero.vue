<script setup>
/**
 * Porrop landing hero, "orbit" layout, for vendors (WhatsApp sellers and small shops).
 *
 * The feeling to land: relieved, proud, in control, safe.
 *   - Relieved: the WhatsApp chaos is on the LEFT, faded and tired. It is the past.
 *   - Proud / in control: on the RIGHT, crisp cards tell the new story in order:
 *     your store is live -> an order is paid -> you see exactly what you keep.
 *   - Safe: calm colours, soft shadows, plain pricing, no shouting.
 *
 * Desktop (xl+): cards orbit the headline. Below xl: the same cards sit in a small cluster
 * under the buttons, because most vendors will see this on a phone.
 *
 * No vendor counts or earnings claims: there is no real data yet. Add real proof later.
 * Pure CSS motion, no images.
 *
 * Needs the theme in tailwind.config.js (ink, primary, accent, neutral, whatsapp, shadow-soft).
 */
import { Link } from '@inertiajs/vue3'

defineProps({
    eyebrow: { type: String, default: 'For WhatsApp sellers and small shops in Nigeria' },
    headlineTop: { type: String, default: 'Run your WhatsApp business' },
    headlineBottom: { type: String, default: 'like a real shop.' },
    subcopy: {
        type: String,
        default:
            'Give customers one link to see your prices, order and pay securely. No more screenshots, no more chasing for transfers. You see every order in one place.',
    },
    signupHref: { type: String, default: '/vendor/register' },
    signupLabel: { type: String, default: 'Open my free store' },
    // Leave empty to hide the WhatsApp button until you have a number to use.
    whatsappHref: { type: String, default: '' },
    whatsappLabel: { type: String, default: 'Talk to us on WhatsApp' },
    // Keep in sync with config/porrop.php. Change here if the pricing changes.
    pricingNote: { type: String, default: 'No setup fee. No monthly fee. We only earn when you sell.' },
    commissionChips: {
        type: Array,
        default: () => ['10% per sale', 'Minimum ₦100', 'Never more than ₦5,000 per order'],
    },
    reassurance: {
        type: String,
        default: 'You set your prices, you see every order, and you stay in control of your business.',
    },
    exampleUrl: { type: String, default: 'porrop.com.ng/store/your-name' },
})

const cardBase = 'w-40 rounded-2xl border px-3.5 py-3 text-left min-[420px]:w-44 sm:w-48 2xl:w-52'

// Full class strings on purpose, so Tailwind can see them.
// Left side = the past (faded). Right side = the new way (crisp).
const kinds = {
    chat: 'rounded-tl-sm border-ink/10 bg-white/70 text-ink/60',
    reply: 'rounded-tr-sm border-transparent bg-whatsapp/15 text-ink/60',
    lost: 'rounded-tl-sm border-dashed border-ink/20 bg-white/40 text-ink/40',
    store: 'border-transparent bg-primary text-white shadow-soft-lg',
    order: 'border-ink/10 bg-white text-ink shadow-soft',
    payout: 'border-transparent bg-accent text-ink shadow-soft',
}

// lg = position in the desktop orbit (percentages of the section). sm = position in the mobile cluster.
const cards = [
    { id: 'c1', kind: 'chat', label: 'Customer', title: 'Hi, how much?', rot: '-rotate-2', lg: 'left-[4%] top-[9%]', sm: 'left-0 top-0' },
    { id: 'c2', kind: 'reply', label: 'You', title: '₦12,000. Sending the pic again…', rot: 'rotate-1', lg: 'left-[11%] top-[37%]', sm: null },
    { id: 'c3', kind: 'lost', label: 'Customer', title: 'Is it still available?', sub: 'Seen yesterday. No reply.', rot: '-rotate-1', lg: 'left-[3%] top-[66%]', sm: 'left-0 top-[8.5rem]' },
    { id: 'p1', kind: 'store', label: 'Your store is live', title: 'Your name. Your prices.', sub: 'Live at /store/your-name', rot: 'rotate-1', lg: 'right-[4%] top-[8%]', sm: 'right-0 top-6' },
    { id: 'p2', kind: 'order', label: 'Order paid', title: '₦12,000', sub: 'Order #1042 · ready to pack', rot: '-rotate-1', lg: 'right-[10%] top-[36%]', sm: 'right-0 top-[9.5rem]' },
    { id: 'p3', kind: 'payout', label: 'You keep', title: '₦10,800', sub: 'Porrop fee ₦1,200 (10%)', rot: 'rotate-1', lg: 'right-[3%] top-[65%]', sm: 'left-[22%] bottom-0' },
]
const mobileCards = cards.filter((c) => c.sm)
</script>

<template>
    <section class="relative isolate overflow-hidden bg-neutral-bg">
        <!-- Quiet depth: one big soft glow, nothing busy -->
        <div class="pointer-events-none absolute left-1/2 top-[-10rem] -z-10 h-[34rem] w-[52rem] -translate-x-1/2 rounded-full bg-primary-light/70 blur-3xl" aria-hidden="true" />

        <!-- Orbit (desktop only) -->
        <div class="pointer-events-none absolute inset-0 hidden xl:block" aria-hidden="true">
            <div
                v-for="(c, i) in cards"
                :key="c.id"
                class="float absolute"
                :class="c.lg"
                :style="{ animationDelay: `${i * 0.7}s`, animationDuration: `${7 + (i % 3)}s` }"
            >
                <div :class="[cardBase, kinds[c.kind], c.rot]">
                    <p class="text-[10px] font-semibold uppercase tracking-wide opacity-70">{{ c.label }}</p>
                    <p class="mt-0.5 break-words text-sm font-semibold leading-snug">{{ c.title }}</p>
                    <p v-if="c.sub" class="mt-0.5 break-words text-[11px] leading-snug opacity-75">{{ c.sub }}</p>
                </div>
            </div>
        </div>

        <div class="relative z-10 mx-auto max-w-6xl px-6 pb-16 pt-12 lg:pt-16 xl:pb-24 xl:pt-24">
            <!-- Pitch -->
            <div class="mx-auto max-w-3xl text-center 2xl:max-w-4xl">
                <p class="hero-in hero-in--1 inline-flex items-center gap-2 rounded-full border border-ink/10 bg-white px-3.5 py-1.5 text-xs font-medium text-ink shadow-soft">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-whatsapp" aria-hidden="true" />
                    {{ eyebrow }}
                </p>

                <h1 class="hero-in hero-in--2 mt-6 font-heading text-balance text-[2.15rem] font-semibold leading-[1.08] tracking-tight text-ink sm:text-5xl xl:text-[3.25rem] 2xl:text-6xl">
                    <span class="block">{{ headlineTop }}</span>
                    <span class="block text-primary">{{ headlineBottom }}</span>
                </h1>

                <p class="hero-in hero-in--3 mx-auto mt-6 max-w-xl text-base leading-relaxed text-ink/70 sm:text-lg">
                    {{ subcopy }}
                </p>

                <div class="hero-in hero-in--4 mt-8 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                    <Link
                        :href="signupHref"
                        class="inline-flex items-center justify-center rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-ink shadow-soft transition duration-200 hover:-translate-y-0.5 hover:bg-accent-light hover:shadow-soft-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-bg active:translate-y-0"
                    >
                        {{ signupLabel }}
                    </Link>

                    <a
                        v-if="whatsappHref"
                        :href="whatsappHref"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center justify-center gap-2.5 rounded-xl border border-ink/15 bg-white px-5 py-3.5 text-sm font-semibold text-ink transition duration-200 hover:border-whatsapp hover:bg-whatsapp/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-bg"
                    >
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-whatsapp text-white">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Zm4.4-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.6.1-.2.3-.6.8-.8 1-.1.1-.3.2-.5.1a6.5 6.5 0 0 1-3.3-2.9c-.2-.4.2-.4.5-1.3.1-.1.1-.3 0-.4-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.2s1 2.5 1.1 2.7c.1.1 2 3 4.8 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1-.1-.1-.2-.2-.5-.3Z" />
                            </svg>
                        </span>
                        {{ whatsappLabel }}
                    </a>
                </div>

                <p class="hero-in hero-in--5 mt-5 text-xs text-ink/60">
                    Your store link: <span class="font-semibold text-ink">{{ exampleUrl }}</span>
                </p>

                <div class="hero-in hero-in--5 mt-7">
                    <p class="text-sm font-semibold text-ink">{{ pricingNote }}</p>
                    <ul class="mt-3 flex flex-wrap justify-center gap-2">
                        <li
                            v-for="chip in commissionChips"
                            :key="chip"
                            class="rounded-full border border-ink/10 bg-white px-3 py-1 text-xs font-medium text-ink/80"
                        >
                            {{ chip }}
                        </li>
                    </ul>
                </div>

                <p class="hero-in hero-in--5 mx-auto mt-8 max-w-xl rounded-2xl bg-primary px-5 py-4 text-sm font-medium leading-relaxed text-white shadow-soft">
                    {{ reassurance }}
                </p>
            </div>

            <!-- Cluster (below xl) -->
            <div class="hero-in hero-in--visual relative mx-auto mt-14 h-[22rem] w-full max-w-sm sm:max-w-md xl:hidden" aria-hidden="true">
                <div
                    v-for="(c, i) in mobileCards"
                    :key="c.id"
                    class="float absolute"
                    :class="c.sm"
                    :style="{ animationDelay: `${i * 0.8}s`, animationDuration: `${7 + (i % 3)}s` }"
                >
                    <div :class="[cardBase, kinds[c.kind], c.rot]">
                        <p class="text-[10px] font-semibold uppercase tracking-wide opacity-70">{{ c.label }}</p>
                        <p class="mt-0.5 break-words text-sm font-semibold leading-snug">{{ c.title }}</p>
                        <p v-if="c.sub" class="mt-0.5 break-words text-[11px] leading-snug opacity-75">{{ c.sub }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Cards drift gently. Rotation lives on the inner element so the two transforms don't fight. */
.float {
    animation: float 7s ease-in-out infinite alternate;
}
@keyframes float {
    from { transform: translateY(0); }
    to { transform: translateY(-6px); }
}

/* One entrance on load: fade + small rise, staggered. Only opacity/transform change. */
.hero-in {
    opacity: 0;
    transform: translateY(14px);
    animation: hero-fade-up 0.6s ease-out forwards;
}
.hero-in--1 { animation-delay: 0.05s; }
.hero-in--2 { animation-delay: 0.15s; }
.hero-in--3 { animation-delay: 0.27s; }
.hero-in--4 { animation-delay: 0.38s; }
.hero-in--5 { animation-delay: 0.48s; }
.hero-in--visual { animation-delay: 0.3s; animation-duration: 0.8s; }

@keyframes hero-fade-up {
    to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .hero-in { opacity: 1; transform: none; animation: none; }
    .float { animation: none; }
}
</style>
