<script setup>
import { computed, ref } from 'vue'

// `form` is an Inertia useForm({ name, description, status, variants: [{id?, variant_name, price, stock_quantity}] })
const props = defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'Save product' },
})
const emit = defineEmits(['submit'])

// "Options mode" shows the name field. It is on when the product already has options.
const optionsMode = ref(props.form.variants.length > 1 || !!props.form.variants[0]?.variant_name)
const many = computed(() => optionsMode.value || props.form.variants.length > 1)

const addOption = () => {
    if (!many.value) {
        optionsMode.value = true // first click only reveals the name field on the existing row
        return
    }
    props.form.variants.push({ variant_name: '', price: props.form.variants[0]?.price ?? '', stock_quantity: 0 })
}
const clearOptions = () => {
    optionsMode.value = false
    props.form.variants[0].variant_name = ''
}
const removeOption = (i) => {
    if (props.form.variants.length > 1) props.form.variants.splice(i, 1)
}
const err = (key) => props.form.errors[key]

const field = 'mt-1 block w-full rounded-lg border-primary/20 bg-white shadow-sm focus:border-primary focus:ring-primary'
</script>

<template>
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <div class="rounded-2xl bg-white p-5 shadow-soft sm:p-6">
            <div>
                <label for="name" class="text-sm font-medium">Product name</label>
                <input id="name" v-model="form.name" type="text" :class="field" placeholder="Leather tote bag" />
                <p v-if="err('name')" class="mt-1 text-sm text-red-700">{{ err('name') }}</p>
            </div>

            <div class="mt-5">
                <label for="description" class="text-sm font-medium">Description <span class="font-normal text-neutral-text/50">(optional)</span></label>
                <textarea id="description" v-model="form.description" rows="4" :class="field" placeholder="Size, material, condition, delivery notes..." />
                <p v-if="err('description')" class="mt-1 text-sm text-red-700">{{ err('description') }}</p>
            </div>

            <div class="mt-5">
                <label for="status" class="text-sm font-medium">Visibility</label>
                <select id="status" v-model="form.status" :class="field">
                    <option value="active">Live: shown in your store</option>
                    <option value="draft">Draft: hidden from customers</option>
                </select>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-soft sm:p-6">
            <h2 class="font-heading text-xl font-semibold text-primary-dark">{{ many ? 'Options' : 'Price and stock' }}</h2>
            <p v-if="many" class="mt-1 text-sm text-neutral-text/70">Each option has its own price and stock, like sizes or colours.</p>

            <div class="mt-4 space-y-4">
                <div
                    v-for="(v, i) in form.variants"
                    :key="v.id ?? `new-${i}`"
                    :class="many ? 'rounded-xl border border-primary/15 p-4' : ''"
                >
                    <div v-if="many" class="mb-3">
                        <label :for="`vn-${i}`" class="text-sm font-medium">Option name</label>
                        <input :id="`vn-${i}`" v-model="v.variant_name" type="text" :class="field" placeholder="Small, Red, 500g..." />
                        <p v-if="err(`variants.${i}.variant_name`)" class="mt-1 text-sm text-red-700">{{ err(`variants.${i}.variant_name`) }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label :for="`vp-${i}`" class="text-sm font-medium">Price (₦)</label>
                            <input :id="`vp-${i}`" v-model="v.price" type="number" inputmode="decimal" min="1" step="0.01" :class="field" placeholder="12000" />
                            <p v-if="err(`variants.${i}.price`)" class="mt-1 text-sm text-red-700">{{ err(`variants.${i}.price`) }}</p>
                        </div>
                        <div>
                            <label :for="`vs-${i}`" class="text-sm font-medium">In stock</label>
                            <input :id="`vs-${i}`" v-model="v.stock_quantity" type="number" inputmode="numeric" min="0" step="1" :class="field" />
                            <p v-if="err(`variants.${i}.stock_quantity`)" class="mt-1 text-sm text-red-700">{{ err(`variants.${i}.stock_quantity`) }}</p>
                        </div>
                    </div>

                    <p v-if="err(`variants.${i}.id`)" class="mt-2 text-sm text-red-700">{{ err(`variants.${i}.id`) }}</p>

                    <button v-if="many" type="button" class="mt-3 text-sm font-medium text-red-700 hover:underline" @click="removeOption(i)">
                        Remove this option
                    </button>
                </div>
            </div>

            <p v-if="err('variants')" class="mt-2 text-sm text-red-700">{{ err('variants') }}</p>

            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2">
                <button type="button" class="text-sm font-semibold text-primary underline" @click="addOption">
                    {{ many ? '+ Add another option' : 'This product comes in sizes, colours or other options' }}
                </button>
                <button v-if="many && form.variants.length === 1" type="button" class="text-sm font-medium text-neutral-text/70 underline" @click="clearOptions">
                    No options, just one price
                </button>
            </div>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-xl bg-accent px-5 py-3 text-base font-semibold text-ink shadow-soft transition hover:bg-accent-light focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60 sm:w-auto"
        >
            {{ form.processing ? 'Saving...' : submitLabel }}
        </button>
    </form>
</template>
