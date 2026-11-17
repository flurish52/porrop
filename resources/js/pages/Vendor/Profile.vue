<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import VendorLayout from '@/Layouts/VendorLayout.vue'
import { compressImage } from '@/utils/compressImage'

const LIMITS = {
    logo: { maxSide: 400, targetBytes: 60 * 1024 },     // ~60 KB
    banner: { maxSide: 1600, targetBytes: 250 * 1024 }, // ~250 KB
}
const compressing = ref(false)


defineOptions({ layout: VendorLayout })

const props = defineProps({
    profile: { type: Object, required: true },
    storeUrl: { type: String, required: true },
    directoryUrl: { type: String, required: true },
    updateUrl: { type: String, required: true },
    cloudinaryReady: { type: Boolean, default: true },
    nav: { type: Object, default: () => ({}) },
})

const form = useForm({
    _method: 'put',
    name: props.profile.name,
    tagline: props.profile.tagline,
    description: props.profile.description,
    whatsapp_number: props.profile.whatsapp_number,
    email: props.profile.email,
    listed_in_directory: props.profile.listed,
    logo: null,
    banner: null,
    remove_logo: false,
    remove_banner: false,
})

/* ---- image previews: show the new file straight away, before it is saved ---- */
// One reactive object, addressed by field name ('logo' | 'banner'), so the template
// never has to pass a ref into a function (templates unwrap refs to their value).
const previews = reactive({ logo: null, banner: null })

const clearPreview = (field) => {
    if (previews[field]) URL.revokeObjectURL(previews[field])
    previews[field] = null
}

const setFile = async (field, event) => {
    const picked = event.target.files?.[0] ?? null
    clearPreview(field)
    form[`remove_${field}`] = false

    if (!picked) {
        form[field] = null
        return
    }

    compressing.value = true
    try {
        const file = await compressImage(picked, LIMITS[field])
        form[field] = file
        previews[field] = URL.createObjectURL(file)
    } finally {
        compressing.value = false
    }
}

onBeforeUnmount(() => {
    clearPreview('logo')
    clearPreview('banner')
})

const logoShown = computed(() => previews.logo ?? (form.remove_logo ? null : props.profile.logo))
const bannerShown = computed(() => previews.banner ?? (form.remove_banner ? null : props.profile.banner))
const initial = computed(() => (form.name || 'S').trim().charAt(0).toUpperCase())

const removeImage = (field, inputId) => {
    clearPreview(field)
    form[field] = null
    form[`remove_${field}`] = true
    const el = document.getElementById(inputId)
    if (el) el.value = ''
}

const submit = () =>
    form.post(props.updateUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.logo = null
            form.banner = null
            form.remove_logo = false
            form.remove_banner = false
            clearPreview('logo')
            clearPreview('banner')
        },
    })

/* ---- share box ---- */
const copied = ref(false)
const copy = async () => {
    try {
        await navigator.clipboard.writeText(props.storeUrl)
    } catch {
        const el = document.getElementById('store-link')
        el?.select()
        document.execCommand?.('copy')
    }
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}
const whatsappShare = computed(() => `https://wa.me/?text=${encodeURIComponent(`Shop at ${props.profile.name}: ${props.storeUrl}`)}`)

const field = 'mt-1 block w-full rounded-lg border-primary/20 bg-white shadow-sm focus:border-primary focus:ring-primary'
const err = (k) => form.errors[k]
</script>

<template>
    <Head title="Store profile" />

    <div class="mx-auto max-w-3xl">
        <h1 class="font-heading text-3xl font-semibold text-primary-dark">Store profile</h1>
        <p class="mt-1 text-sm text-neutral-text/70">This is what customers see when they open your store link.</p>

        <!-- Share box -->
        <section class="mt-6 rounded-2xl bg-white p-5 shadow-soft sm:p-6" aria-labelledby="share-title">
            <h2 id="share-title" class="text-base font-semibold text-primary-dark">Your store link</h2>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input id="store-link" :value="storeUrl" readonly class="block w-full rounded-lg border-primary/20 bg-neutral-bg text-sm shadow-sm focus:border-primary focus:ring-primary" @focus="$event.target.select()" />
                <div class="flex gap-2">
                    <button type="button" class="flex-1 rounded-lg border border-primary/25 px-4 py-2 text-sm font-semibold text-primary-dark hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:flex-none" @click="copy">
                        <span aria-live="polite">{{ copied ? 'Copied' : 'Copy' }}</span>
                    </button>
                    <a :href="whatsappShare" target="_blank" rel="noopener" class="flex-1 rounded-lg bg-whatsapp px-4 py-2 text-center text-sm font-bold text-ink hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:flex-none">Share</a>
                    <a :href="storeUrl" target="_blank" rel="noopener" class="flex-1 rounded-lg border border-primary/25 px-4 py-2 text-center text-sm font-semibold text-primary-dark hover:bg-primary-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:flex-none">View</a>
                </div>
            </div>
            <p class="mt-2 text-xs text-neutral-text/60">Put this link in your WhatsApp status, Instagram bio and Facebook page.</p>
        </section>

        <p v-if="!cloudinaryReady" class="mt-4 rounded-lg bg-accent/20 px-4 py-3 text-sm text-ink" role="status">Image uploads are not set up on this server yet, so the logo and banner cannot be changed.</p>

        <form class="mt-6 space-y-8" @submit.prevent="submit">
            <!-- Look -->
            <section class="rounded-2xl bg-white p-5 shadow-soft sm:p-6" aria-labelledby="look-title">
                <h2 id="look-title" class="text-base font-semibold text-primary-dark">Logo and banner</h2>

                <!-- Live preview of the top of the store -->
                <div class="relative mt-4 overflow-hidden rounded-xl bg-primary-dark text-white" aria-hidden="true">
                    <img v-if="bannerShown" :src="bannerShown" alt="" class="absolute inset-0 h-full w-full object-cover" />
                    <div class="absolute inset-0 bg-primary-dark/70"></div>
                    <div class="relative flex items-center gap-3 px-4 py-5">
                        <img v-if="logoShown" :src="logoShown" alt="" class="h-12 w-12 flex-none rounded-full object-cover" />
                        <span v-else class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-accent font-heading text-xl font-semibold text-ink">{{ initial }}</span>
                        <div class="min-w-0">
                            <p class="truncate font-heading text-xl font-semibold">{{ form.name || 'Your store' }}</p>
                            <p v-if="form.tagline" class="truncate text-sm text-white/80">{{ form.tagline }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="logo-input" class="text-sm font-medium">Logo</label>
                        <input id="logo-input" type="file" accept="image/jpeg,image/png,image/webp" :disabled="!cloudinaryReady" class="mt-1 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary-light file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-dark hover:file:bg-primary-light/70" @change="setFile('logo', $event)" />
                        <p class="mt-1 text-xs text-neutral-text/60">Square works best. JPG, PNG or WebP, up to 3 MB.</p>
                        <button v-if="logoShown" type="button" class="mt-1 text-xs font-medium text-red-700 underline" @click="removeImage('logo', 'logo-input')">Remove logo</button>
                        <p v-if="err('logo')" class="mt-1 text-sm text-red-700" role="alert">{{ err('logo') }}</p>
                    </div>
                    <div>
                        <label for="banner-input" class="text-sm font-medium">Banner</label>
                        <input id="banner-input" type="file" accept="image/jpeg,image/png,image/webp" :disabled="!cloudinaryReady" class="mt-1 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary-light file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-dark hover:file:bg-primary-light/70" @change="setFile('banner', $event)" />
                        <p class="mt-1 text-xs text-neutral-text/60">Wide photo of your products or shop. Up to 5 MB.</p>
                        <button v-if="bannerShown" type="button" class="mt-1 text-xs font-medium text-red-700 underline" @click="removeImage('banner', 'banner-input')">Remove banner</button>
                        <p v-if="err('banner')" class="mt-1 text-sm text-red-700" role="alert">{{ err('banner') }}</p>
                    </div>
                </div>
            </section>

            <!-- Words -->
            <section class="space-y-5 rounded-2xl bg-white p-5 shadow-soft sm:p-6" aria-labelledby="about-title">
                <h2 id="about-title" class="text-base font-semibold text-primary-dark">About your store</h2>

                <div>
                    <label for="name" class="text-sm font-medium">Store name</label>
                    <input id="name" v-model="form.name" type="text" maxlength="80" autocomplete="organization" :class="field" />
                    <p class="mt-1 text-xs text-neutral-text/60">Your link stays the same if you change the name.</p>
                    <p v-if="err('name')" class="mt-1 text-sm text-red-700" role="alert">{{ err('name') }}</p>
                </div>

                <div>
                    <label for="tagline" class="text-sm font-medium">Tagline <span class="font-normal text-neutral-text/60">(one short line)</span></label>
                    <input id="tagline" v-model="form.tagline" type="text" maxlength="100" placeholder="e.g. Quality menswear in Calabar" :class="field" />
                    <p class="mt-1 text-right text-xs text-neutral-text/60">{{ form.tagline.length }}/100</p>
                    <p v-if="err('tagline')" class="text-sm text-red-700" role="alert">{{ err('tagline') }}</p>
                </div>

                <div>
                    <label for="description" class="text-sm font-medium">Description</label>
                    <textarea id="description" v-model="form.description" rows="5" maxlength="800" placeholder="What you sell, where you are, how delivery works." :class="field"></textarea>
                    <p class="mt-1 text-right text-xs text-neutral-text/60">{{ form.description.length }}/800</p>
                    <p v-if="err('description')" class="text-sm text-red-700" role="alert">{{ err('description') }}</p>
                </div>
            </section>

            <!-- Contact -->
            <section class="space-y-5 rounded-2xl bg-white p-5 shadow-soft sm:p-6" aria-labelledby="contact-title">
                <h2 id="contact-title" class="text-base font-semibold text-primary-dark">How customers reach you</h2>

                <div>
                    <label for="whatsapp" class="text-sm font-medium">WhatsApp number</label>
                    <input id="whatsapp" v-model="form.whatsapp_number" type="tel" inputmode="tel" autocomplete="tel" placeholder="0803 123 4567" :class="field" />
                    <p class="mt-1 text-xs text-neutral-text/60">Adds a Chat button to your store and product pages. Leave empty to hide it.</p>
                    <p v-if="err('whatsapp_number')" class="mt-1 text-sm text-red-700" role="alert">{{ err('whatsapp_number') }}</p>
                </div>

                <div>
                    <label for="email" class="text-sm font-medium">Store email</label>
                    <input id="email" v-model="form.email" type="email" autocomplete="email" :class="field" />
                    <p class="mt-1 text-xs text-neutral-text/60">Order receipts are sent in your store's name. When a customer replies, it comes here.</p>
                    <p v-if="err('email')" class="mt-1 text-sm text-red-700" role="alert">{{ err('email') }}</p>
                </div>
            </section>

            <!-- Directory -->
            <section class="rounded-2xl bg-white p-5 shadow-soft sm:p-6" aria-labelledby="listing-title">
                <h2 id="listing-title" class="text-base font-semibold text-primary-dark">Visibility</h2>
                <label class="mt-3 flex cursor-pointer items-start gap-3">
                    <input v-model="form.listed_in_directory" type="checkbox" class="mt-1 h-5 w-5 rounded border-primary/30 text-primary focus:ring-primary" />
                    <span class="text-sm">
                        <span class="font-medium">Show my store on the <a :href="directoryUrl" target="_blank" rel="noopener" class="text-primary underline">Porrop stores page</a></span>
                        <span class="mt-0.5 block text-neutral-text/70">More people can find you. Your store needs at least one live product to appear. Turn this off if you only want to share your own link.</span>
                    </span>
                </label>
            </section>

            <div class="sticky bottom-0 -mx-4 border-t border-primary/10 bg-neutral-bg/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0">
                <button type="submit" :disabled="form.processing || compressing"
                        class="w-full rounded-xl bg-accent px-6 py-3 text-base font-bold text-ink shadow-soft transition hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60 sm:w-auto">
                    {{ compressing ? 'Preparing image...' : form.processing ? 'Saving...' : 'Save profile' }}
                </button>
            </div>
        </form>
    </div>
</template>
