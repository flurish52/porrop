<script setup>
/**
 * Layout for Porrop's vendor-facing marketing pages (landing, pricing, FAQ).
 *
 * Replaces the pharmacy layout: no cart, no shop nav. The header has one job: get a vendor
 * to "Open my free store". Mobile keeps that button visible next to the menu so it is always
 * one tap away.
 *
 * Needs from the app:
 *   - Ziggy route() with 'login' and 'dashboard' (Breeze defaults)
 *   - Optional shared Inertia prop `supportWhatsapp` (digits only, e.g. 234XXXXXXXXXX).
 *     Share it in HandleInertiaRequests. If it is missing the WhatsApp button is hidden.
 *   - Sections with ids #how-it-works, #pricing, #faq on the landing page (to be built).
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppFooter from '@/Components/AppFooter.vue'
import VendorMarquee from '@/Components/Landing/VendorMarquee.vue'

const page = usePage()

const mobileMenuOpen = ref(false)
const scrolled = ref(false)

// Plain anchors: these jump within the landing page, so they are <a>, not Inertia <Link>.
const navLinks = [
    { label: 'How it works', href: '/#how-it-works' },
    { label: 'Stores', href: '/stores' },
    { label: 'Pricing', href: '/#pricing' },
    { label: 'FAQ', href: '/#faq' },
]

const signupHref = '/vendor/register'
const signupLabel = 'Open my free store'

/* --- Auth --- */
const authUser = computed(() => page.props.auth?.user ?? null)
const firstName = computed(() => authUser.value?.name?.split(' ')[0] ?? '')
const supportWhatsapp = computed(() => page.props.supportWhatsapp ?? null)

/* --- Sticky header gets a blur once you leave the top --- */
const onScroll = () => {
    scrolled.value = window.scrollY > 8
}

const onKeydown = (e) => {
    if (e.key === 'Escape') mobileMenuOpen.value = false
}

onMounted(() => {
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll)
    window.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})

/* Drawer floats above the page, so freeze the page behind it */
watch(mobileMenuOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
})

/* Close the drawer after any Inertia visit */
watch(() => page.url, () => {
    mobileMenuOpen.value = false
})
</script>

<template>
    <div class="flex min-h-screen flex-col bg-neutral-bg text-neutral-text">
        <VendorMarquee />

        <header
            class="sticky top-0 z-40 border-b border-ink/10 transition-shadow duration-200"
            :class="scrolled
                ? 'bg-neutral-bg/90 backdrop-blur-md supports-[backdrop-filter]:bg-neutral-bg/80'
                : 'bg-neutral-bg'"
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <!-- Wordmark: built in CSS, no logo file needed -->
                <Link
                    href="/"
                    class="group inline-flex items-center gap-2.5 rounded-lg font-heading text-xl font-semibold lowercase tracking-tight text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-bg sm:text-2xl"
                    aria-label="Porrop home"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary font-heading text-lg font-semibold leading-none text-accent shadow-soft transition-transform duration-200 ease-out group-hover:-rotate-6 group-active:scale-95 motion-reduce:transition-none"
                        aria-hidden="true"
                    >p</span>
                    <span class="leading-none">porrop</span>
                </Link>

                <!-- Desktop nav -->
                <nav class="hidden items-center gap-1 md:flex" aria-label="Main">
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-md px-3 py-1.5 text-sm font-medium text-ink/70 transition-colors duration-200 hover:bg-primary-light hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary motion-reduce:transition-none"
                    >
                        {{ link.label }}
                    </a>
                </nav>

                <div class="flex items-center gap-2">
                    <Link
                        :href="authUser ? route('dashboard') : route('login')"
                        class="hidden items-center gap-2 rounded-md px-2.5 py-2 text-sm font-semibold text-ink/70 transition-colors hover:bg-primary-light hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary md:flex"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 21a8 8 0 0 0-16 0" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        {{ authUser ? firstName : 'Sign in' }}
                    </Link>

                    <!-- Primary CTA, desktop -->
                    <Link
                        :href="signupHref"
                        class="hidden items-center justify-center rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-ink shadow-soft transition duration-200 hover:bg-accent-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-bg md:inline-flex"
                    >
                        {{ signupLabel }}
                    </Link>

                    <!-- Primary CTA, mobile: short label so it fits next to the menu button -->
                    <Link
                        :href="signupHref"
                        class="inline-flex items-center justify-center rounded-xl bg-accent px-3.5 py-1.5 text-sm font-semibold text-ink shadow-soft transition duration-200 active:bg-accent-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary md:hidden"
                    >
                        Start free
                    </Link>

                    <button
                        class="flex h-10 w-10 items-center justify-center rounded-md text-ink transition-colors hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary md:hidden"
                        aria-label="Open menu"
                        :aria-expanded="mobileMenuOpen"
                        aria-controls="mobile-drawer"
                        @click="mobileMenuOpen = true"
                    >
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <line x1="3" y1="6" x2="21" y2="6" />
                            <line x1="3" y1="12" x2="21" y2="12" />
                            <line x1="3" y1="18" x2="21" y2="18" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Mobile drawer -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-200"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="mobileMenuOpen"
                    class="fixed inset-0 z-50 bg-ink/50 md:hidden"
                    @click="mobileMenuOpen = false"
                ></div>
            </Transition>

            <Transition
                enter-active-class="transition-transform duration-300 ease-out motion-reduce:transition-none"
                enter-from-class="translate-x-full"
                leave-active-class="transition-transform duration-200 ease-in motion-reduce:transition-none"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="mobileMenuOpen"
                    id="mobile-drawer"
                    class="fixed inset-y-0 right-0 z-50 flex w-72 max-w-[85vw] flex-col border-l border-ink/10 bg-neutral-bg shadow-soft-lg md:hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Menu"
                >
                    <div class="flex items-center justify-between border-b border-ink/10 px-4 py-3.5">
                        <span class="font-heading text-lg font-semibold lowercase text-ink">porrop</span>
                        <button
                            class="flex h-9 w-9 items-center justify-center rounded-md text-ink transition-colors hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            aria-label="Close menu"
                            @click="mobileMenuOpen = false"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    <nav class="flex-1 overflow-y-auto" aria-label="Mobile">
                        <ul class="divide-y divide-ink/10">
                            <li v-for="link in navLinks" :key="link.href">
                                <a
                                    :href="link.href"
                                    class="flex items-center justify-between px-4 py-4 text-base font-semibold text-ink transition-colors hover:bg-primary-light"
                                    @click="mobileMenuOpen = false"
                                >
                                    {{ link.label }}
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="text-ink/40" aria-hidden="true">
                                        <path d="m9 6 6 6-6 6" />
                                    </svg>
                                </a>
                            </li>
                            <li>
                                <Link
                                    :href="authUser ? route('dashboard') : route('login')"
                                    class="flex items-center justify-between px-4 py-4 text-base font-semibold text-ink transition-colors hover:bg-primary-light"
                                    @click="mobileMenuOpen = false"
                                >
                                    {{ authUser ? `Signed in as ${firstName}` : 'Sign in' }}
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="text-ink/40" aria-hidden="true">
                                        <path d="m9 6 6 6-6 6" />
                                    </svg>
                                </Link>
                            </li>
                        </ul>
                    </nav>

                    <div class="space-y-3 border-t border-ink/10 px-4 py-4">
                        <Link
                            :href="signupHref"
                            class="flex items-center justify-center rounded-xl bg-accent py-3 text-sm font-semibold text-ink shadow-soft"
                            @click="mobileMenuOpen = false"
                        >
                            {{ signupLabel }}
                        </Link>

                        <a
                            v-if="supportWhatsapp"
                            :href="`https://wa.me/${supportWhatsapp}`"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center justify-center gap-2 rounded-xl border border-ink/15 bg-white py-3 text-sm font-semibold text-ink"
                        >
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-whatsapp text-white">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Z" />
                                </svg>
                            </span>
                            Talk to us on WhatsApp
                        </a>
                    </div>
                </aside>
            </Transition>
        </Teleport>

        <main class="flex-1">
            <slot />
        </main>

        <AppFooter />
    </div>
</template>
