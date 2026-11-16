<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const flash = computed(() => page.props.flash ?? {})
</script>

<template>
    <div class="min-h-screen bg-neutral-bg text-neutral-text">
        <header class="border-b border-primary/10 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4 sm:px-6">
                <Link href="/" class="font-heading text-2xl font-semibold text-primary">Porrop</Link>
                <slot name="header" />
            </div>
        </header>

        <div v-if="flash.success" class="mx-auto mt-4 max-w-3xl px-4 sm:px-6">
            <p class="rounded-lg bg-primary-light px-4 py-3 text-sm text-primary-dark">{{ flash.success }}</p>
        </div>
        <div v-if="flash.error" class="mx-auto mt-4 max-w-3xl px-4 sm:px-6">
            <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{{ flash.error }}</p>
        </div>

        <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-12">
            <slot />
        </main>
    </div>
</template>
