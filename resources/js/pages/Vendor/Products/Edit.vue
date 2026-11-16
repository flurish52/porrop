<script setup>
import { computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import VendorLayout from '@/Layouts/VendorLayout.vue'
import ProductForm from '@/Components/Vendor/ProductForm.vue'
import ProductImages from '@/Components/Vendor/ProductImages.vue'

defineOptions({ layout: VendorLayout })

const props = defineProps({
    product: { type: Object, required: true },
    updateUrl: { type: String, required: true },
    deleteUrl: { type: String, required: true },
    uploadUrl: { type: String, required: true },
    maxImages: { type: Number, default: 10 },
    nav: { type: Object, default: () => ({}) },
})

const form = useForm({
    name: props.product.name,
    description: props.product.description ?? '',
    status: props.product.status,
    variants: props.product.variants.map((v) => ({ ...v })),
})

// After a save the server returns the options WITH ids. Reload them into the form, otherwise a newly
// added option keeps no id and the next save would create it a second time.
const submit = () =>
    form.put(props.updateUrl, {
        preserveScroll: true,
        onSuccess: () => {
            form.variants = props.product.variants.map((v) => ({ ...v }))
        },
    })

// Saved options only: photos can only be linked to options that exist in the database.
const savedVariants = computed(() =>
    props.product.variants.map((v) => ({ id: v.id, name: v.variant_name || 'Default' })),
)

const destroy = () => {
    if (confirm(`Delete "${props.product.name}"? It will disappear from your store.`)) router.delete(props.deleteUrl)
}
</script>

<template>
    <Head :title="product.name" />

    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <Link :href="nav.products || '#'" class="text-sm font-medium text-primary underline">&larr; All products</Link>
            <h1 class="mt-2 truncate font-heading text-3xl font-semibold text-primary-dark">{{ product.name }}</h1>
        </div>

        <ProductImages :images="product.images" :variants="savedVariants" :upload-url="uploadUrl" :max="maxImages" />

        <ProductForm :form="form" submit-label="Save changes" @submit="submit" />

        <div class="rounded-2xl border border-red-200 bg-white p-5">
            <h2 class="text-sm font-semibold text-red-800">Delete this product</h2>
            <p class="mt-1 text-sm text-neutral-text/70">It leaves your store. Past orders keep their details.</p>
            <button type="button" class="mt-3 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-50" @click="destroy">
                Delete product
            </button>
        </div>
    </div>
</template>
