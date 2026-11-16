<script setup>
/**
 * FAQ. Anchor target for #faq. Plain <details>, so it works without JavaScript and is
 * keyboard and screen-reader friendly.
 *
 * CONFIRM BEFORE LAUNCH: every answer below is a promise to vendors. Payout timing is left
 * out on purpose (not decided yet). Add it here once it is.
 */
defineProps({
    heading: { type: String, default: 'Questions sellers ask us' },
    items: {
        type: Array,
        default: () => [
            {
                q: 'How much does it cost?',
                a: 'Nothing to start and nothing every month. Porrop takes 10% of each sale, with a minimum of ₦100 and a maximum of ₦5,000 on any single order.',
            },
            {
                q: 'Can I still use WhatsApp with my customers?',
                a: 'Yes. Keep chatting like you do today. Porrop just gives them one place to see your prices, order and pay, so you stop repeating yourself.',
            },
            {
                q: 'How do customers pay, and how do I get paid?',
                a: 'Customers pay online when they order, through Paystack. Your share goes to the bank account you add when you set up your store, and you can see every order and payment in your dashboard.',
            },
            {
                q: 'Do I need a website or any tech skills?',
                a: 'No. You open a store, add your products and share your link. If you can post a WhatsApp status, you can do this.',
            },
            {
                q: 'What can I sell?',
                a: 'Products. Add photos, prices and stock for each item. Porrop is for selling things, not for booking services.',
            },
            {
                q: 'What if I want to stop?',
                a: 'You can close your store whenever you want. There is no monthly fee, so there is nothing to cancel.',
            },
        ],
    },
})
</script>

<template>
    <section id="faq" class="scroll-mt-20 border-t border-ink/10 bg-white py-16 md:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center font-heading text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ heading }}</h2>

            <div class="mt-8 divide-y divide-ink/10 border-y border-ink/10">
                <details v-for="item in items" :key="item.q" class="group py-1">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-4 rounded-md py-4 text-left text-base font-semibold text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary [&::-webkit-details-marker]:hidden"
                    >
                        {{ item.q }}
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-light text-primary transition-transform duration-200 group-open:rotate-45 motion-reduce:transition-none"
                            aria-hidden="true"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
                        </span>
                    </summary>
                    <p class="pb-4 pr-10 text-sm leading-relaxed text-ink/70">{{ item.a }}</p>
                </details>
            </div>
        </div>
    </section>
</template>
