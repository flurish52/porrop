<script setup>
import StoreCard from '@/Components/Stores/StoreCard.vue'

defineProps({
    stores: { type: Array, required: true },
})
</script>

<template>
    <section id="stores" class="border-y border-primary/10 bg-white/60 py-12 sm:py-16" aria-labelledby="stores-title">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="max-w-xl">
                    <h2 id="stores-title" class="font-heading text-3xl font-semibold text-primary-dark sm:text-4xl">Already selling on Porrop</h2>
                    <p class="mt-2 text-neutral-text/75">Real stores with real products. Take a look, or open yours next.</p>
                </div>
                <a href="/stores" class="text-sm font-semibold text-primary underline">See all stores &rarr;</a>
            </div>

            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="s in stores" :key="s.id"><StoreCard :store="s" compact /></li>
            </ul>
        </div>
    </section>
</template>
