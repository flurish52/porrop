// Shrinks an image in the browser so big phone photos upload quickly.
// Returns the original file if it is already small enough, or if anything fails.
const QUALITIES = [0.85, 0.75, 0.65, 0.55, 0.45]

const loadBitmap = async (file) => {
    try {
        // Applies the phone's EXIF rotation so photos don't come out sideways
        return await createImageBitmap(file, { imageOrientation: 'from-image' })
    } catch {
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

export async function compressImage(file, { maxSide = 1600, targetBytes = 300 * 1024 } = {}) {
    if (!file || !file.type.startsWith('image/') || file.size <= targetBytes) return file

    try {
        const bitmap = await loadBitmap(file)
        const srcW = bitmap.width || bitmap.naturalWidth
        const srcH = bitmap.height || bitmap.naturalHeight
        let best = null

        for (const step of [1, 0.75, 0.5]) {
            const ratio = Math.min(1, maxSide / Math.max(srcW, srcH)) * step
            const canvas = document.createElement('canvas')
            canvas.width = Math.max(1, Math.round(srcW * ratio))
            canvas.height = Math.max(1, Math.round(srcH * ratio))
            const ctx = canvas.getContext('2d')
            ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height)

            for (const q of QUALITIES) {
                // WebP keeps transparency (PNG logos). Browsers that can't encode WebP return PNG, so retry as JPEG.
                let blob = await toBlob(canvas, 'image/webp', q)
                if (!blob || blob.type !== 'image/webp') {
                    ctx.globalCompositeOperation = 'destination-over'
                    ctx.fillStyle = '#fff'
                    ctx.fillRect(0, 0, canvas.width, canvas.height)
                    blob = await toBlob(canvas, 'image/jpeg', q)
                }
                if (!blob) continue
                if (!best || blob.size < best.size) best = blob
                if (blob.size <= targetBytes) break
            }
            if (best && best.size <= targetBytes) break
        }

        if (bitmap.close) bitmap.close()
        if (!best || best.size >= file.size) return file // never make it bigger

        const ext = best.type === 'image/webp' ? 'webp' : 'jpg'
        const base = file.name.replace(/\.[^.]+$/, '') || 'image'
        return new File([best], `${base}.${ext}`, { type: best.type, lastModified: Date.now() })
    } catch {
        return file
    }
}
