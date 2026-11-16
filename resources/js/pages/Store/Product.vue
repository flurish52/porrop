<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import StoreLayout from '@/Layouts/StoreLayout.vue'

defineOptions({ layout: StoreLayout })

const props = defineProps({
    product: { type: Object, required: true },
    addUrl: { type: String, required: true },
    shop: { type: Object, default: () => ({}) },
})
const page = usePage()

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`

const firstInStock = props.product.variants.find((v) => v.stock > 0) ?? props.product.variants[0]
const form = useForm({ variant_id: firstInStock?.id, quantity: 1 })

const variant = computed(() => props.product.variants.find((v) => v.id === form.variant_id))
const many = computed(() => props.product.variants.length > 1)
const soldOut = computed(() => !variant.value || variant.value.stock < 1)
const priceFrom = computed(() => Math.min(...props.product.variants.map((v) => v.price)))

const inCart = computed(() => page.props.shop?.cart?.quantities?.[form.variant_id] ?? 0)

// How many more the customer can add: stock minus what's already in the cart
const maxQty = computed(() => Math.max(Math.min((variant.value?.stock ?? 0) - inCart.value, 99), 0))
const allInCart = computed(() => !soldOut.value && maxQty.value < 1)

const decrease = () => {
    if (form.quantity > 1) form.quantity--
}
const increase = () => {
    if (form.quantity < maxQty.value) form.quantity++
}

const picked = ref(null)
const shown = computed(() => {
    const imgs = props.product.images
    if (picked.value !== null && imgs[picked.value]) return imgs[picked.value]
    return imgs.find((i) => i.variant_id === form.variant_id) ?? imgs.find((i) => i.is_primary) ?? imgs[0] ?? null
})

const choose = (id) => {
    form.variant_id = id
    form.quantity = 1
    picked.value = null
}

const submit = () =>
    form.post(props.addUrl, {
        preserveScroll: true,
        onSuccess: () => {
            form.quantity = 1
        },
    })

const askHref = computed(() =>
    props.shop.whatsapp_number
        ? `https://wa.me/${props.shop.whatsapp_number}?text=${encodeURIComponent(`Hello, I have a question about ${props.product.name}.`)}`
        : '',
)
</script>

<template>
    <Head :title="product.name" />

    <Link :href="shop.url || '/'" class="text-sm font-medium text-primary underline">&larr; Back to store</Link>

    <div class="mt-4 grid gap-8 md:grid-cols-2">
        <div>
            <div class="aspect-square overflow-hidden rounded-2xl bg-secondary shadow-soft">
                <img v-if="shown?.url" :src="shown.url" :alt="product.name" class="h-full w-full object-cover" />
            </div>
            <ul v-if="product.images.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1">
                <li v-for="(img, i) in product.images" :key="i" class="flex-none">
                    <button type="button" class="h-16 w-16 overflow-hidden rounded-lg border-2 bg-secondary" :class="shown === img ? 'border-primary' : 'border-transparent'" :aria-label="`Photo ${i + 1}`" @click="picked = i">
                        <img v-if="img.url" :src="img.url" alt="" class="h-full w-full object-cover" loading="lazy" />
                    </button>
                </li>
            </ul>
        </div>

        <div>
            <h1 class="font-heading text-3xl font-semibold text-primary-dark">{{ product.name }}</h1>
            <p class="mt-2 text-2xl font-semibold text-primary">
                {{ variant ? naira(variant.price) : naira(priceFrom) }}
            </p>

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <fieldset v-if="many">
                    <legend class="text-sm font-medium">Choose an option</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <label
                            v-for="v in product.variants"
                            :key="v.id"
                            class="cursor-pointer rounded-lg border px-4 py-2 text-sm transition focus-within:ring-2 focus-within:ring-primary"
                            :class="[
                                form.variant_id === v.id ? 'border-primary bg-primary text-white' : 'border-primary/25 bg-white hover:border-primary',
                                v.stock < 1 ? 'opacity-50' : '',
                            ]"
                        >
                            <input type="radio" class="sr-only" name="variant" :value="v.id" :checked="form.variant_id === v.id" @change="choose(v.id)" />
                            {{ v.name }}<span v-if="v.stock < 1"> (sold out)</span>
                        </label>
                    </div>
                </fieldset>

                <div v-if="!soldOut && !allInCart">
                    <span id="qty-label" class="text-sm font-medium">Quantity</span>
                    <div class="mt-1 inline-flex items-center rounded-lg border border-primary/25 bg-white" role="group" aria-labelledby="qty-label">
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center text-lg font-semibold text-primary transition hover:bg-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="form.quantity <= 1"
                            aria-label="Decrease quantity"
                            @click="decrease"
                        >
                            &minus;
                        </button>
                        <span class="w-12 text-center text-base font-medium" aria-live="polite">{{ form.quantity }}</span>
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center text-lg font-semibold text-primary transition hover:bg-secondary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="form.quantity >= maxQty"
                            aria-label="Increase quantity"
                            @click="increase"
                        >
                            +
                        </button>
                    </div>
                    <p v-if="variant.stock <= 5" class="mt-1 text-xs text-accent-dark">Only {{ variant.stock }} left</p>
                </div>

                <p v-if="allInCart" class="text-sm font-medium text-accent-dark" role="status">
                    You have all available stock of this item in your cart.
                </p>
                <p v-else-if="inCart" class="text-sm font-medium text-primary" role="status">{{ inCart }} in your cart</p>
                <p v-for="(e, k) in form.errors" :key="k" class="text-sm text-red-700" role="alert">{{ e }}</p>

                <button
                    type="submit"
                    :disabled="soldOut || allInCart || form.processing"
                    class="w-full rounded-xl bg-accent px-5 py-3 text-base font-semibold text-ink shadow-soft transition hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ soldOut ? 'Sold out' : allInCart ? 'Max in cart' : form.processing ? 'Adding...' : 'Add to cart' }}
                </button>
            </form>

            <a v-if="askHref" :href="askHref" target="_blank" rel="noopener" class="mt-4 inline-block text-sm font-medium text-primary underline">
                Ask the seller a question on WhatsApp
            </a>

            <p v-if="product.description" class="mt-8 whitespace-pre-line text-sm leading-relaxed text-neutral-text/80">{{ product.description }}</p>
        </div>
    </div>
</template>
