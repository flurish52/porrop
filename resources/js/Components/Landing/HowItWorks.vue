<script setup>
/**
 * Three steps. Anchor target for the "How it works" nav link (#how-it-works).
 * The point is relief: it looks easy, and it is clear nobody needs tech skills.
 */
import { Link } from '@inertiajs/vue3'

defineProps({
    heading: { type: String, default: 'Open your store in three steps' },
    lead: { type: String, default: 'No website, no developer, no tech skills.' },
    signupHref: { type: String, default: '/vendor/register' },
    signupLabel: { type: String, default: 'Open my free store' },
    steps: {
        type: Array,
        default: () => [
            {
                title: 'Open your store',
                text: 'Add your business name, logo and WhatsApp number. You get your own store link straight away.',
            },
            {
                title: 'Add your products',
                text: 'Upload photos, set your prices and stock. Change them any time, whenever you like.',
            },
            {
                title: 'Share your link, get orders',
                text: 'Put it in your WhatsApp status, your bio and your chats. Customers order and pay, and you see it all in your dashboard.',
            },
        ],
    },
})
</script>

<template>
    <section id="how-it-works" class="scroll-mt-20 border-y border-ink/10 bg-white py-16 md:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-heading text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ heading }}</h2>
                <p class="mt-3 text-base leading-relaxed text-ink/65">{{ lead }}</p>
            </div>

            <ol class="mt-10 grid gap-5 md:grid-cols-3">
                <li
                    v-for="(step, i) in steps"
                    :key="step.title"
                    class="relative rounded-2xl border border-ink/10 bg-neutral-bg p-6"
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-accent font-heading text-lg font-semibold text-ink">
                        {{ i + 1 }}
                    </span>
                    <h3 class="mt-4 font-heading text-lg font-semibold text-ink">{{ step.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink/70">{{ step.text }}</p>
                </li>
            </ol>

            <div class="mt-10 text-center">
                <Link
                    :href="signupHref"
                    class="inline-flex items-center justify-center rounded-xl bg-accent px-7 py-3.5 text-base font-semibold text-ink shadow-soft transition duration-200 hover:-translate-y-0.5 hover:bg-accent-light hover:shadow-soft-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                >
                    {{ signupLabel }}
                </Link>
            </div>
        </div>
    </section>
</template>
