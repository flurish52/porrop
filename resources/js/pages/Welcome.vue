<script setup>
/**
 * Porrop home page (vendor-facing landing). Composition only: every section owns its own markup,
 * so sections can be reordered or reused.
 *
 * Order tells one story: hook -> why it is safe -> how easy -> what it costs -> doubts -> go.
 *
 * Route (outside the /store/{vendor} group):
 *   Route::get('/', fn () => Inertia::render('Welcome', ['commission' => config('porrop.commission')]))->name('home');
 *
 * Passing `commission` from config keeps the hero chips and the calculator identical to what
 * CommissionCalculator really charges.
 *
 * `defineOptions({ layout })` assigns the layout for this page. If your app.js already sets a
 * default layout in resolve(), remove it for this page or this line (not both).
 */
import {computed} from 'vue'
import {Head, usePage} from '@inertiajs/vue3'
import LandingLayout from '@/Layouts/LandingLayout.vue'
import VendorHero from '@/Components/Landing/VendorHero.vue'
import WhyPorrop from '@/Components/Landing/WhyPorrop.vue'
import HowItWorks from '@/Components/Landing/HowItWorks.vue'
import Pricing from '@/Components/Landing/Pricing.vue'
import Faq from '@/Components/Landing/Faq.vue'
import ClosingCta from '@/Components/Landing/ClosingCta.vue'
import StoresStrip from '@/Components/Landing/StoresStrip.vue'


defineOptions({
    layout: LandingLayout,
    })

const props = defineProps({
    commission: {type: Object, default: () => ({rate: 10, min: 100, max: 5000})},
    stores: {
        type: Array, default: () => []
    }
})

const page = usePage()

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`

const chips = computed(() => [
    `${props.commission.rate}% per sale`,
    `Minimum ${naira(props.commission.min)}`,
    `Never more than ${naira(props.commission.max)} per order`,
])

const whatsappHref = computed(() => {
    const number = page.props.supportWhatsapp
    return number
        ? `https://wa.me/${number}?text=${encodeURIComponent('Hello Porrop, I would like to open a store.')}`
        : ''
})
</script>

<template>
    <Head title="Porrop: run your WhatsApp business like a real shop">
        <meta
            head-key="description"
            name="description"
            content="Open your own online store in minutes. Customers see your prices, order and pay online, and you see every order in one place. No setup fee, no monthly fee."
        />
    </Head>

    <div>
        <VendorHero :commission-chips="chips" :whatsapp-href="whatsappHref"/>
        <WhyPorrop/>
        <HowItWorks/>
        <StoresStrip v-if="stores.length" :stores="stores" />
        <Pricing :rate="commission.rate" :min="commission.min" :max="commission.max"/>
        <Faq/>
        <ClosingCta/>
    </div>
</template>
