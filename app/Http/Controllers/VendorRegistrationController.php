<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VendorRegistrationController extends Controller
{
    private const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function create(): Response
    {
        return Inertia::render('Vendor/Register', [
            'prefix' => config('porrop.vendor_prefix'),
            'host' => parse_url(config('app.url'), PHP_URL_HOST),
        ]);
    }

    /** Live "is this link free?" check used by the register form. */
    public function slugCheck(Request $request): JsonResponse
    {
        $slug = Str::lower((string) $request->query('slug'));

        if (! $this->slugShapeOk($slug)) {
            return response()->json(['available' => false, 'reason' => 'Use 3-40 letters, numbers or single dashes.']);
        }

        if (in_array($slug, config('porrop.reserved_slugs'), true)) {
            return response()->json(['available' => false, 'reason' => 'That name is reserved.']);
        }

        $taken = Vendor::withTrashed()->where('slug', $slug)->exists();
        return response()->json([
            'available' => ! $taken,
            'reason' => $taken ? 'Someone already has this link.' : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'slug' => Str::lower((string) $request->input('slug')),
            'whatsapp_number' => $this->normalizeWhatsapp((string) $request->input('whatsapp_number')),
        ]);

        $guest = ! Auth::check();

        $data = $request->validate([
            'store_name' => ['required', 'string', 'min:2', 'max:80'],
            'slug' => [
                'required', 'string', 'min:3', 'max:40', 'regex:'.self::SLUG_REGEX,
                Rule::notIn(config('porrop.reserved_slugs')),
                Rule::unique('vendors', 'slug'),
            ],
            'whatsapp_number' => ['required', 'regex:/^234\d{10}$/'],
            'name' => [$guest ? 'required' : 'nullable', 'string', 'max:255'],
            'email' => [$guest ? 'required' : 'nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => [$guest ? 'required' : 'nullable', 'string', 'min:8'],
        ], [
            'whatsapp_number.regex' => 'Enter a Nigerian number like 0803 123 4567.',
            'slug.regex' => 'Use letters, numbers and single dashes only.',
            'slug.unique' => 'Someone already has this link. Try another.',
        ]);

        $vendor = DB::transaction(function () use ($data, $guest) {
            $user = $guest
                ? User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['whatsapp_number'],
                    'password' => Hash::make($data['password']),
                ])
                : Auth::user();

            $vendor = Vendor::create([
                'name' => $data['store_name'],
                'slug' => $data['slug'],
                'whatsapp_number' => $data['whatsapp_number'],
                'phone' => $data['whatsapp_number'],
                'email' => $user->email,
                'status' => config('porrop.auto_approve') ? Vendor::STATUS_ACTIVE : Vendor::STATUS_PENDING,
            ]);

            $vendor->users()->attach($user->id, ['role' => 'owner']);

            return [$vendor, $user];
        });

        [$vendor, $user] = $vendor;

        if ($guest) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->route('vendor.dashboard', $vendor)->with('success', 'Your store is ready.');
    }

    private function slugShapeOk(string $slug): bool
    {
        return strlen($slug) >= 3 && strlen($slug) <= 40 && preg_match(self::SLUG_REGEX, $slug) === 1;
    }

    /** 0803..., +234803..., 234803... -> 234803XXXXXXX. Anything else is returned untouched and fails validation. */
    private function normalizeWhatsapp(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        if (preg_match('/^0\d{10}$/', $digits)) {
            return '234'.substr($digits, 1);
        }

        if (preg_match('/^234\d{10}$/', $digits)) {
            return $digits;
        }

        return $digits;
    }
}
