<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import LandingLayout from '@/Layouts/LandingLayout.vue'
import StoreCard from '@/Components/Stores/StoreCard.vue'

defineOptions({ layout: LandingLayout })

const props = defineProps({
    stores: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    q: { type: String, default: '' },
    prevUrl: { type: String, default: null },
    nextUrl: { type: String, default: null },
    page: { type: Number, default: 1 },
    lastPage: { type: Number, default: 1 },
    searchUrl: { type: String, required: true },
    registerUrl: { type: String, default: '/vendor/register' },
})

const term = ref(props.q)

const search = () => {
    const q = term.value.trim()
    router.get(props.searchUrl, q ? { q } : {}, { preserveScroll: false })
}
const clear = () => {
    term.value = ''
    router.get(props.searchUrl)
}
</script>

<template>
    <Head title="Stores on Porrop">
        <meta head-key="description" name="description" content="Browse online stores on Porrop. See what each seller has, then visit their store to order and pay online." />
    </Head>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <header class="max-w-2xl">
            <h1 class="font-heading text-4xl font-semibold text-primary-dark sm:text-5xl">Stores on Porrop</h1>
            <p class="mt-3 text-base leading-relaxed text-neutral-text/80">
                Browse real sellers, look at what they have, then visit their store to order and pay online.
            </p>
        </header>

        <form class="mt-6 flex max-w-xl gap-2" role="search" @submit.prevent="search">
            <label for="store-search" class="sr-only">Search stores</label>
            <input id="store-search" v-model="term" type="search" maxlength="60" placeholder="Search stores" class="block w-full rounded-xl border-primary/20 bg-white shadow-sm focus:border-primary focus:ring-primary" />
            <button type="submit" class="flex-none rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">Search</button>
        </form>

        <p class="mt-4 max-w-2xl rounded-lg bg-primary-light px-4 py-3 text-sm text-primary-dark">
            Each store is run by its own seller and checks out on its own, so you pay one store at a time.
        </p>

        <p v-if="q" class="mt-6 text-sm text-neutral-text/70" role="status">
            {{ total }} {{ total === 1 ? 'store' : 'stores' }} for &ldquo;{{ q }}&rdquo;
            <button type="button" class="ml-2 font-medium text-primary underline" @click="clear">Clear search</button>
        </p>

        <p v-if="!stores.length" class="mt-8 rounded-2xl bg-white p-8 text-center text-sm text-neutral-text/70 shadow-soft">
            {{ q ? 'No stores match your search.' : 'No stores to show yet. Check back soon.' }}
        </p>

        <ul v-else class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="s in stores" :key="s.id"><StoreCard :store="s" /></li>
        </ul>

        <nav v-if="prevUrl || nextUrl" class="mt-8 flex items-center justify-between text-sm" aria-label="Pages">
            <Link v-if="prevUrl" :href="prevUrl" class="rounded-lg border border-primary/25 px-4 py-2 font-semibold text-primary-dark hover:bg-primary-light">&larr; Newer</Link>
            <span v-else></span>
            <span class="text-neutral-text/60">Page {{ page }} of {{ lastPage }}</span>
            <Link v-if="nextUrl" :href="nextUrl" class="rounded-lg border border-primary/25 px-4 py-2 font-semibold text-primary-dark hover:bg-primary-light">Older &rarr;</Link>
            <span v-else></span>
        </nav>

        <section class="mt-14 rounded-2xl bg-primary-dark px-6 py-8 text-white sm:px-10">
            <h2 class="font-heading text-2xl font-semibold">Sell something? Open your own store.</h2>
            <p class="mt-2 max-w-xl text-sm text-white/80">Free to start. You get a store link you can share on WhatsApp, and customers can order and pay online.</p>
            <a :href="registerUrl" class="mt-4 inline-block rounded-xl bg-accent px-5 py-2.5 text-sm font-bold text-ink shadow-soft hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-white">Open my free store</a>
        </section>
    </div>
</template>
