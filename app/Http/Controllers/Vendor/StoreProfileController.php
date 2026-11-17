<?php

namespace App\Http\Controllers\Vendor;

use App\Services\CloudinaryException;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/** The store's public face: logo, banner, name, tagline, description, WhatsApp. Owner only (see the route). */
class StoreProfileController extends VendorAreaController
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function edit(): Response
    {
        $vendor = app('currentVendor');

        return $this->page('Vendor/Profile', [
            'profile' => [
                'name' => $vendor->name,
                'tagline' => $vendor->tagline ?? '',
                'description' => $vendor->description ?? '',
                'whatsapp_number' => $vendor->whatsapp_number ?? '',
                'email' => $vendor->email ?? '',
                'listed' => (bool) $vendor->listed_in_directory,
                'logo' => $vendor->logo_url,
                'banner' => $vendor->banner_url,
            ],
            'storeUrl' => route('store.show', ['vendor' => $vendor]),
            'directoryUrl' => route('stores.index'),
            'updateUrl' => route('vendor.profile.update', ['vendor' => $vendor]),
            'cloudinaryReady' => filled(config('porrop.cloudinary_cloud')) && filled(config('porrop.cloudinary_key')) && filled(config('porrop.cloudinary_secret')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $vendor = app('currentVendor');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:800'],
            'whatsapp_number' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'logo.image' => 'The logo must be a photo (JPG, PNG or WebP).',
            'logo.max' => 'The logo must be under 3 MB.',
            'banner.image' => 'The banner must be a photo (JPG, PNG or WebP).',
            'banner.max' => 'The banner must be under 5 MB.',
        ]);

        $whatsapp = $this->normalizeWhatsapp($data['whatsapp_number'] ?? null);

        // Upload first. If anything fails, nothing is saved and nothing is left behind.
        $uploaded = [];

        foreach (['logo' => 'logo_public_id', 'banner' => 'banner_public_id'] as $field => $column) {
            if (! $request->hasFile($field)) {
                continue;
            }

            try {
                $uploaded[$column] = $this->cloudinary->upload($request->file($field), "porrop/vendor-{$vendor->id}/brand");
            } catch (CloudinaryException $e) {
                foreach ($uploaded as $id) {
                    $this->cloudinary->destroy($id);
                }

                throw ValidationException::withMessages([$field => $e->getMessage()]);
            }
        }

        $changes = [
            'name' => trim($data['name']),
            'tagline' => $this->clean($data['tagline'] ?? null),
            'description' => $this->clean($data['description'] ?? null),
            'whatsapp_number' => $whatsapp,
            'email' => $this->clean($data['email'] ?? null),
            'listed_in_directory' => $request->boolean('listed_in_directory'),
        ] + $uploaded;

        // "Remove" boxes clear an image when no new one was uploaded in its place.
        foreach (['logo' => 'logo_public_id', 'banner' => 'banner_public_id'] as $field => $column) {
            if ($request->boolean("remove_{$field}") && ! isset($uploaded[$column])) {
                $changes[$column] = null;
            }
        }

        // Remember what gets replaced so the old files can be deleted once the save has worked.
        $replaced = [];
        foreach (['logo_public_id', 'banner_public_id'] as $column) {
            if (array_key_exists($column, $changes) && $vendor->{$column} && $vendor->{$column} !== $changes[$column]) {
                $replaced[] = $vendor->{$column};
            }
        }

        $vendor->update($changes);

        foreach ($replaced as $publicId) {
            $this->cloudinary->destroy($publicId);
        }

        return back()->with('success', 'Store profile saved.');
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** 0803 123 4567, +234 803 123 4567 and 803... all become 2348031234567. Anything else is rejected. */
    private function normalizeWhatsapp(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '234'.substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '234'.$digits;
        }

        if (! preg_match('/^234\d{10}$/', $digits)) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Enter a Nigerian number, for example 0803 123 4567.',
            ]);
        }

        return $digits;
    }
}
