<?php

return [
    // Stores live at {APP_URL}/{vendor_prefix}/{slug}
    'vendor_prefix' => 'store',

    // v1: one flat commission for every vendor (percent of subtotal, clamped to min/max in naira).
    'commission' => [
        'rate' => (float) env('PORROP_COMMISSION_RATE', 10),
        'min' => (float) env('PORROP_COMMISSION_MIN', 100),
        'max' => (float) env('PORROP_COMMISSION_MAX', 5000),
    ],

    // true  = new stores are live immediately (fastest MVP launch)
    // false = stores start 'pending' until a platform admin activates them
    'auto_approve' => (bool) env('PORROP_AUTO_APPROVE', true),

    // Digits only, international format e.g. 2348012345678
    'support_whatsapp' => env('PORROP_SUPPORT_WHATSAPP'),

    // Cloud name is public (used to build image URLs). Key + secret are server-only (uploads/deletes).
    'cloudinary_cloud' => env('CLOUDINARY_CLOUD_NAME'),
    'cloudinary_key' => env('CLOUDINARY_API_KEY'),
    'cloudinary_secret' => env('CLOUDINARY_API_SECRET'),

    // Per-product image limit
    'max_images_per_product' => 10,

    // Slugs that would collide with real routes or look official
    'reserved_slugs' => [
        'admin', 'api', 'login', 'register', 'dashboard', 'store', 'stores', 'paystack',
        'porrop', 'support', 'help', 'about', 'pricing', 'terms', 'privacy', 'www',
    ],

    'payout_mode' => ($m = env('PORROP_PAYOUT_MODE', 'auto')) === 'auto'
        ? (str_starts_with((string) env('PAYSTACK_SECRET_KEY'), 'sk_live_') ? 'split' : 'manual')
        : $m,

    'fee_bearer' => env('PORROP_FEE_BEARER', 'account'),   // account = Porrop pays Paystack's fee out of its commission
    'verify_bank_accounts' => (bool) env('PORROP_VERIFY_BANK', true),

    // An order becomes payable this many days after the customer paid, even if the vendor never marks it completed.
    'payout_hold_days' => (int) env('PORROP_PAYOUT_HOLD_DAYS', 0),

];
