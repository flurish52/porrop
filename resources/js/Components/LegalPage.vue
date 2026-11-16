<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { legal } from '@/legal.js'

defineProps({
    title: { type: String, required: true },
    intro: { type: String, default: '' },
})

const others = [
    { href: '/terms', label: 'Terms of Use' },
    { href: '/seller-terms', label: 'Seller Terms' },
    { href: '/privacy', label: 'Privacy Policy' },
    { href: '/refunds', label: 'Refunds & Returns' },
]
</script>

<template>
    <Head :title="title" />

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
        <p class="text-sm font-medium text-primary"><Link href="/" class="underline">&larr; Porrop</Link></p>
        <h1 class="mt-3 font-heading text-4xl font-semibold text-primary-dark sm:text-5xl">{{ title }}</h1>
        <p class="mt-2 text-sm text-neutral-text/60">Effective {{ legal.effective }}</p>
        <p v-if="intro" class="mt-6 rounded-xl bg-primary-light px-5 py-4 text-base leading-relaxed text-primary-dark">{{ intro }}</p>

        <article class="legal mt-8">
            <slot />
        </article>

        <nav class="mt-12 border-t border-primary/10 pt-6 text-sm" aria-label="Other legal pages">
            <p class="font-medium text-neutral-text/70">Also read</p>
            <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-2">
                <li v-for="o in others" :key="o.href"><Link :href="o.href" class="font-medium text-primary underline">{{ o.label }}</Link></li>
            </ul>
        </nav>
    </div>
</template>

<style>
.legal h2 { margin-top: 2.5rem; scroll-margin-top: 6rem; font-family: Fraunces, Georgia, serif; font-size: 1.5rem; font-weight: 600; color: #0B3A3E; }
.legal h3 { margin-top: 1.5rem; font-weight: 600; color: #0B2E33; }
.legal p { margin-top: 0.75rem; line-height: 1.75; color: rgb(15 23 42 / 0.85); }
.legal ul { margin-top: 0.75rem; padding-left: 1.25rem; list-style: disc; }
.legal li { margin-top: 0.4rem; line-height: 1.7; color: rgb(15 23 42 / 0.85); }
.legal a { color: #0F5257; text-decoration: underline; }
.legal strong { color: #0B2E33; }
</style>
