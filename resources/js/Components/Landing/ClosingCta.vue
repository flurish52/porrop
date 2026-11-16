<script setup>
/**
 * Closing band. Replaces the pharmacy "Talk to a pharmacist" banner.
 * Petrol band, saffron button, and WhatsApp green only on the WhatsApp link where it is a real
 * signal. The WhatsApp link needs the shared Inertia prop `supportWhatsapp` (digits only,
 * e.g. 234XXXXXXXXXX) and is hidden without it.
 */
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    heading: { type: String, default: 'Your customers are already asking “how much?” Give them a store.' },
    lead: { type: String, default: 'Open your store in minutes. No setup fee, no monthly fee.' },
    signupHref: { type: String, default: '/vendor/register' },
    signupLabel: { type: String, default: 'Open my free store' },
    message: { type: String, default: 'Hello Porrop, I would like to open a store.' },
})

const page = usePage()

const whatsappHref = computed(() => {
    const number = page.props.supportWhatsapp
    return number ? `https://wa.me/${number}?text=${encodeURIComponent(props.message)}` : null
})
</script>

<template>
    <section class="relative overflow-hidden bg-primary-dark">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/5" />
        <div class="pointer-events-none absolute -bottom-20 left-1/4 h-48 w-48 rounded-full bg-white/5" />

        <div class="relative mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-12 text-white sm:px-6 md:flex-row md:items-center md:justify-between md:py-14">
            <div class="max-w-xl">
                <span class="inline-flex items-center rounded-md bg-white/10 px-2.5 py-1 text-xs font-medium text-white/80">
                    Ready when you are
                </span>
                <h2 class="mt-3 font-heading text-2xl font-semibold leading-tight tracking-tight sm:text-3xl">
                    {{ heading }}
                </h2>
                <p class="mt-2 text-sm leading-relaxed text-white/70">{{ lead }}</p>
            </div>

            <div class="flex w-full shrink-0 flex-col gap-3 sm:w-auto sm:flex-row">
                <Link
                    :href="signupHref"
                    class="inline-flex items-center justify-center rounded-xl bg-accent px-6 py-3.5 text-base font-semibold text-ink shadow-soft transition duration-200 hover:-translate-y-0.5 hover:bg-accent-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary-dark"
                >
                    {{ signupLabel }}
                </Link>

                <a
                    v-if="whatsappHref"
                    :href="whatsappHref"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 px-5 py-3.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                >
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-whatsapp text-white">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Z" />
                        </svg>
                    </span>
                    Message us on WhatsApp
                </a>
            </div>
        </div>
    </section>
</template>
