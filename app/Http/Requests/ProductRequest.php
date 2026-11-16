<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // vendor membership is enforced by route middleware
    }

    public function rules(): array
    {
        $many = count((array) $this->input('variants', [])) > 1;

        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'draft'])],

            'variants' => ['required', 'array', 'min:1', 'max:30'],
            'variants.*.id' => ['nullable', 'integer'],
            // A single-variant product is just "the product": its name is not shown, only needed when there are options.
            'variants.*.variant_name' => [$many ? 'required' : 'nullable', 'string', 'max:80'],
            'variants.*.price' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'variants.*.variant_name.required' => 'Give each option a name, like "Small" or "Red".',
            'variants.*.price.required' => 'Enter a price.',
            'variants.*.price.min' => 'Price must be at least ₦1.',
            'variants.*.stock_quantity.required' => 'Enter how many you have (0 if sold out).',
        ];
    }
}
