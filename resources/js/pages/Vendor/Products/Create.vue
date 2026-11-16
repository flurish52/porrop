<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import VendorLayout from '@/Layouts/VendorLayout.vue'
import ProductForm from '@/Components/Vendor/ProductForm.vue'

defineOptions({ layout: VendorLayout })

const props = defineProps({
    storeUrl: { type: String, required: true },
    nav: { type: Object, default: () => ({}) },
})

const form = useForm({
    name: '',
    description: '',
    status: 'active',
    variants: [{ variant_name: '', price: '', stock_quantity: 1 }],
})

const submit = () => form.post(props.storeUrl)
</script>

<template>
    <Head title="Add product" />

    <div class="mx-auto max-w-2xl">
        <Link :href="nav.products || '#'" class="text-sm font-medium text-primary underline">&larr; All products</Link>
        <h1 class="mt-2 font-heading text-3xl font-semibold text-primary-dark">Add a product</h1>
        <p class="mt-1 text-sm text-neutral-text/70">Save the details first, then you can add photos.</p>

        <div class="mt-6">
            <ProductForm :form="form" submit-label="Save and add photos" @submit="submit" />
        </div>
    </div>
</template>
