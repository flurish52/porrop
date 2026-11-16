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
const compressing = ref(false)
const upload = useForm({ images: [], product_variant_id: '' })

const remaining = computed(() => props.max - props.images.length)
const canAttach = computed(() => props.variants.length > 1)
const busy = computed(() => upload.processing || compressing.value)

// ---- Client-side compression -------------------------------------------------
const MAX_SIDE = 1600 // px, longest side
const TARGET_BYTES = 400 * 1024 // aim for ~400 KB or less
const QUALITIES = [0.82, 0.72, 0.62, 0.52]

const loadBitmap = async (file) => {
    try {
        // Applies the phone's EXIF rotation so photos don't come out sideways
        return await createImageBitmap(file, { imageOrientation: 'from-image' })
    } catch {
        // Older browsers: fall back to an <img> element
        const url = URL.createObjectURL(file)
        try {
            return await new Promise((resolve, reject) => {
                const img = new Image()
                img.onload = () => resolve(img)
                img.onerror = reject
                img.src = url
            })
        } finally {
            URL.revokeObjectURL(url)
        }
    }
}

const toBlob = (canvas, type, quality) =>
    new Promise((resolve) => canvas.toBlob(resolve, type, quality))

const compress = async (file) => {
    if (file.size <= TARGET_BYTES) return file // already small, leave it alone

    try {
        const bitmap = await loadBitmap(file)
        const srcW = bitmap.width || bitmap.naturalWidth
        const srcH = bitmap.height || bitmap.naturalHeight
        let best = null

        for (const scaleStep of [1, 0.75, 0.5]) {
            const ratio = Math.min(1, MAX_SIDE / Math.max(srcW, srcH)) * scaleStep
            const canvas = document.createElement('canvas')
            canvas.width = Math.max(1, Math.round(srcW * ratio))
            canvas.height = Math.max(1, Math.round(srcH * ratio))
            const ctx = canvas.getContext('2d')
            ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height)

            for (const q of QUALITIES) {
                // WebP keeps transparency (PNG logos); browsers without WebP encoding return PNG, so we retry as JPEG
                let blob = await toBlob(canvas, 'image/webp', q)
                if (!blob || blob.type !== 'image/webp') {
                    // JPEG has no transparency: paint white behind it
                    ctx.globalCompositeOperation = 'destination-over'
                    ctx.fillStyle = '#fff'
                    ctx.fillRect(0, 0, canvas.width, canvas.height)
                    blob = await toBlob(canvas, 'image/jpeg', q)
                }
                if (!blob) continue
                if (!best || blob.size < best.size) best = blob
                if (blob.size <= TARGET_BYTES) {
                    best = blob
                    break
                }
            }
            if (best && best.size <= TARGET_BYTES) break
        }

        if (bitmap.close) bitmap.close()
        if (!best || best.size >= file.size) return file // never make it bigger

        const ext = best.type === 'image/webp' ? 'webp' : 'jpg'
        const base = file.name.replace(/\.[^.]+$/, '') || 'photo'
        return new File([best], `${base}.${ext}`, { type: best.type, lastModified: Date.now() })
    } catch {
        return file // if anything goes wrong, upload the original
    }
}

const onPick = async (e) => {
    const files = Array.from(e.target.files || []).slice(0, Math.max(remaining.value, 0))
    if (!files.length) return

    compressing.value = true
    try {
        upload.images = await Promise.all(files.map(compress))
    } finally {
        compressing.value = false
    }

    upload.post(props.uploadUrl, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            upload.reset('images')
            if (picker.value) picker.value.value = ''
        },
    })
}
// -----------------------------------------------------------------------------

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
                :class="{ 'pointer-events-none opacity-50': remaining <= 0 || busy }"
            >
                {{ compressing ? 'Preparing photos...' : upload.processing ? 'Uploading...' : remaining <= 0 ? 'Photo limit reached' : 'Add photos' }}
                <input
                    ref="picker"
                    type="file"
                    class="sr-only"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    :disabled="remaining <= 0 || busy"
                    @change="onPick"
                />
            </label>
            <p class="mt-2 text-xs text-neutral-text/60">JPG, PNG or WebP. Large phone photos are shrunk automatically before upload.</p>

            <ul v-if="uploadErrors.length" class="mt-2 space-y-1 text-sm text-red-700" role="alert">
                <li v-for="(e, i) in uploadErrors" :key="i">{{ e }}</li>
            </ul>
        </div>
    </section>
</template>
