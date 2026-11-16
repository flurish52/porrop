<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import VendorLayout from '@/Layouts/VendorLayout.vue'

defineOptions({ layout: VendorLayout })

const props = defineProps({
    vendor: { type: Object, required: true },
    stats: { type: Object, required: true },
    nav: { type: Object, default: () => ({}) },
})

const naira = (n) => `₦${Number(n || 0).toLocaleString('en-NG')}`

const copied = ref(false)
const copy = async () => {
    try {
        await navigator.clipboard.writeText(props.vendor.url)
        copied.value = true
        setTimeout(() => (copied.value = false), 2000)
    } catch {
        window.prompt('Copy your store link:', props.vendor.url)
    }
}

const shareHref = computed(
    () => `https://wa.me/?text=${encodeURIComponent(`Shop with me on Porrop: ${props.vendor.url}`)}`,
)

const checklist = computed(() => [
    { label: 'Open your store', done: true },
    { label: 'Add your first product', done: props.stats.products > 0, href: props.vendor.add_product_url },
    { label: 'Add your bank details for payouts', done: props.vendor.has_payout_details },
    { label: 'Share your link on WhatsApp', done: props.stats.orders > 0 },
])
</script>

<template>
    <Head :title="`${vendor.name} dashboard`" />

    <div class="space-y-6">
        <div>
            <h1 class="font-heading text-3xl font-semibold text-primary-dark">{{ vendor.name }}</h1>
            <p v-if="vendor.status !== 'active'" class="mt-2 rounded-lg bg-accent-light/60 px-3 py-2 text-sm text-ink">
                Your store is waiting for approval. Only you can see it until then.
            </p>
        </div>

        <section class="rounded-2xl bg-primary-dark p-5 text-white shadow-soft-lg">
            <p class="text-sm text-white/70">Your store link</p>
            <p class="mt-1 break-all font-medium">{{ vendor.url }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-ink hover:bg-accent-light" @click="copy">
                    {{ copied ? 'Copied' : 'Copy link' }}
                </button>
                <a :href="shareHref" target="_blank" rel="noopener" class="rounded-lg bg-whatsapp px-4 py-2 text-sm font-semibold text-ink hover:opacity-90">
                    Share on WhatsApp
                </a>
                <a :href="vendor.url" target="_blank" rel="noopener" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-medium hover:bg-white/10">
                    View store
                </a>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl bg-white p-4 shadow-soft">
                <p class="text-xs text-neutral-text/60">Products</p>
                <p class="mt-1 text-2xl font-semibold">{{ stats.products }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-soft">
                <p class="text-xs text-neutral-text/60">Paid orders</p>
                <p class="mt-1 text-2xl font-semibold">{{ stats.orders }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-soft">
                <p class="text-xs text-neutral-text/60">You have earned</p>
                <p class="mt-1 text-2xl font-semibold">{{ naira(stats.earned) }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-soft">
                <p class="text-xs text-neutral-text/60">Waiting to be paid out</p>
                <p class="mt-1 text-2xl font-semibold">{{ naira(stats.awaiting_payout) }}</p>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-soft">
            <h2 class="font-heading text-xl font-semibold text-primary-dark">Get ready to sell</h2>
            <ul class="mt-3 space-y-2">
                <li v-for="item in checklist" :key="item.label" class="flex items-center gap-3 text-sm">
                    <span
                        class="flex h-5 w-5 flex-none items-center justify-center rounded-full text-xs"
                        :class="item.done ? 'bg-primary text-white' : 'border border-primary/30 text-transparent'"
                        aria-hidden="true"
                    >&#10003;</span>
                    <Link v-if="item.href && !item.done" :href="item.href" class="font-medium text-primary underline">{{ item.label }}</Link>
                    <span v-else :class="item.done ? 'text-neutral-text/60 line-through' : ''">{{ item.label }}</span>
                    <span class="sr-only">{{ item.done ? '(done)' : '(to do)' }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>
