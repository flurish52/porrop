<script setup>
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

const props = defineProps({
    images: { type: Array, default: () => [] },
    variants: { type: Array, default: () => [] },
    uploadUrl: { type: String, required: true },
    max: { type: Number, default: 10 },
})

const picker = ref(null)
const upload = useForm({ images: [], product_variant_id: '' })

const remaining = computed(() => props.max - props.images.length)
const canAttach = computed(() => props.variants.length > 1)

const onPick = (e) => {
    const files = Array.from(e.target.files || []).slice(0, Math.max(remaining.value, 0))
    if (!files.length) return
    upload.images = files
    upload.post(props.uploadUrl, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            upload.reset('images')
            if (picker.value) picker.value.value = ''
        },
    })
}

const makeMain = (img) => router.patch(img.updateUrl, { is_primary: true }, { preserveScroll: true })
const attach = (img, e) =>
    router.patch(img.updateUrl, { product_variant_id: e.target.value || null }, { preserveScroll: true })
const remove = (img) => {
    if (confirm('Remove this photo?')) router.delete(img.deleteUrl, { preserveScroll: true })
}

const uploadErrors = computed(() => Object.entries(upload.errors).map(([k, v]) => v))
</script>

<template>
    <section class="rounded-2xl bg-white p-5 shadow-soft sm:p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-heading text-xl font-semibold text-primary-dark">Photos</h2>
            <p class="text-sm text-neutral-text/60">{{ images.length }} of {{ max }}</p>
        </div>
        <p class="mt-1 text-sm text-neutral-text/70">
            The main photo shows in your store.
            <span v-if="canAttach">Link a photo to one option, like a colour, if it shows only that option.</span>
        </p>

        <ul v-if="images.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
            <li v-for="img in images" :key="img.id" class="overflow-hidden rounded-xl border border-primary/15">
                <div class="relative aspect-square bg-secondary">
                    <img v-if="img.url" :src="img.url" alt="" class="h-full w-full object-cover" loading="lazy" />
                    <span v-if="img.is_primary" class="absolute left-2 top-2 rounded-full bg-accent px-2 py-0.5 text-xs font-semibold text-ink">Main</span>
                </div>
                <div class="space-y-2 p-2">
                    <select
                        v-if="canAttach"
                        :value="img.variant_id ?? ''"
                        class="w-full rounded-md border-primary/20 py-1 text-xs focus:border-primary focus:ring-primary"
                        :aria-label="'Link photo to an option'"
                        @change="attach(img, $event)"
                    >
                        <option value="">Whole product</option>
                        <option v-for="v in variants" :key="v.id" :value="v.id">{{ v.name }}</option>
                    </select>
                    <div class="flex items-center justify-between text-xs">
                        <button v-if="!img.is_primary" type="button" class="font-medium text-primary underline" @click="makeMain(img)">Make main</button>
                        <span v-else class="text-neutral-text/50">Main photo</span>
                        <button type="button" class="font-medium text-red-700 hover:underline" @click="remove(img)">Remove</button>
                    </div>
                </div>
            </li>
        </ul>

        <p v-else class="mt-4 rounded-xl bg-secondary-light px-4 py-6 text-center text-sm text-neutral-text/70">
            No photos yet. Products with clear photos sell far better.
        </p>

        <div class="mt-5">
            <div v-if="canAttach" class="mb-3 max-w-xs">
                <label for="upload-variant" class="text-sm font-medium">Upload for</label>
                <select
                    id="upload-variant"
                    v-model="upload.product_variant_id"
                    class="mt-1 block w-full rounded-lg border-primary/20 focus:border-primary focus:ring-primary"
                >
                    <option value="">Whole product</option>
                    <option v-for="v in variants" :key="v.id" :value="v.id">{{ v.name }}</option>
                </select>
            </div>

            <label
                class="inline-flex cursor-pointer items-center rounded-xl border-2 border-dashed border-primary/30 px-5 py-3 text-sm font-semibold text-primary hover:bg-primary-light focus-within:ring-2 focus-within:ring-primary"
                :class="{ 'pointer-events-none opacity-50': remaining <= 0 || upload.processing }"
            >
                {{ upload.processing ? 'Uploading...' : remaining <= 0 ? 'Photo limit reached' : 'Add photos' }}
                <input
                    ref="picker"
                    type="file"
                    class="sr-only"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    :disabled="remaining <= 0 || upload.processing"
                    @change="onPick"
                />
            </label>
            <p class="mt-2 text-xs text-neutral-text/60">JPG, PNG or WebP, up to 5 MB each.</p>

            <ul v-if="uploadErrors.length" class="mt-2 space-y-1 text-sm text-red-700" role="alert">
                <li v-for="(e, i) in uploadErrors" :key="i">{{ e }}</li>
            </ul>
        </div>
    </section>
</template>
