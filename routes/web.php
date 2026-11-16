<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\ProductImageController;
use App\Http\Controllers\VendorRegistrationController;
use App\Http\Controllers\WelcomeController;
use App\Http\Middleware\EnsureVendorMember;
use App\Http\Middleware\ResolveVendor;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\OrderPageController;
use App\Http\Controllers\Store\ProductPageController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\PayoutController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Middleware\EnsurePlatformAdmin;

/*
|--------------------------------------------------------------------------
| Porrop: lean MVP routes
|--------------------------------------------------------------------------
| Middleware is referenced by class so nothing has to be registered in bootstrap/app.php.
*/

Route::get('/', WelcomeController::class)->name('home');
Route::inertia('/terms', 'Legal/Terms')->name('legal.terms');
Route::inertia('/seller-terms', 'Legal/SellerTerms', ['commission' => config('porrop.commission')])->name('legal.seller-terms');
Route::inertia('/privacy', 'Legal/Privacy')->name('legal.privacy');
Route::inertia('/refunds', 'Legal/Refunds')->name('legal.refunds');

// Vendor sign-up (guest creates account + store in one go; a logged-in user can open another store)
Route::get('/vendor/register', [VendorRegistrationController::class, 'create'])->name('vendor.register');
Route::get('/vendor/slug-check', [VendorRegistrationController::class, 'slugCheck'])
    ->middleware('throttle:60,1')->name('vendor.slug-check');
Route::post('/vendor/register', [VendorRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')->name('vendor.register.store');

// Paystack: global URLs (never inside /store/{vendor})
Route::get('/paystack/callback', [PaystackController::class, 'callback'])->name('paystack.callback');
Route::post('/paystack/webhook', [PaystackController::class, 'webhook'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('paystack.webhook');

// Authenticated
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Everything for one store lives under /store/{vendor:slug}
Route::prefix(config('porrop.vendor_prefix', 'store').'/{vendor:slug}')
    ->middleware(ResolveVendor::class)
    ->scopeBindings()
    ->group(function () {

        // Public storefront
        Route::get('/', [StoreController::class, 'show'])->name('store.show');

        Route::get('products/{product:slug}', [ProductPageController::class, 'show'])->name('store.product');

        Route::get('cart', [CartController::class, 'show'])->name('store.cart');
        Route::post('cart', [CartController::class, 'add'])->middleware('throttle:60,1')->name('store.cart.add');
        Route::patch('cart/{variant}', [CartController::class, 'update'])->name('store.cart.update');
        Route::delete('cart/{variant}', [CartController::class, 'remove'])->name('store.cart.remove');

        Route::get('checkout', [CheckoutController::class, 'create'])->name('store.checkout');
        Route::post('checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('store.checkout.store');

        Route::get('orders/{reference}', [OrderPageController::class, 'show'])->middleware('throttle:30,1')->name('store.order');

        // Vendor back office
        Route::middleware(['auth', EnsureVendorMember::class])->prefix('dashboard')->group(function () {
            Route::get('/', VendorDashboardController::class)->name('vendor.dashboard');

            // Products: /store/{vendor}/dashboard/products ...  (names: vendor.products.index/create/store/edit/update/destroy)
            Route::resource('products', ProductController::class)->except('show')->names('vendor.products');

            // Photos. {image} is resolved through the product, {product} through the vendor (scopeBindings).
            Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('vendor.products.images.store');
            Route::patch('products/{product}/images/{image}', [ProductImageController::class, 'update'])->name('vendor.products.images.update');
            Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('vendor.products.images.destroy');

            Route::get('orders', [VendorOrderController::class, 'index'])->name('vendor.orders.index');
            Route::get('orders/{order}', [VendorOrderController::class, 'show'])->name('vendor.orders.show');
            Route::patch('orders/{order}/status', [VendorOrderController::class, 'status'])->name('vendor.orders.status');

            Route::middleware(EnsureVendorMember::class.':owner')->group(function () {
                Route::get('payout', [PayoutController::class, 'edit'])->name('vendor.payout.edit');
                Route::put('payout', [PayoutController::class, 'update'])->name('vendor.payout.update');
                Route::get('payout/check', [PayoutController::class, 'check'])->middleware('throttle:10,1')->name('vendor.payout.check');
            });
        });
    });

// Platform admin (you). Not tenant-scoped: sees every store.
Route::middleware(['auth', EnsurePlatformAdmin::class])->prefix('admin')->group(function () {
    Route::get('payouts', [AdminPayoutController::class, 'index'])->name('admin.payouts.index');
    Route::post('payouts/{vendor}', [AdminPayoutController::class, 'store'])->name('admin.payouts.store');
});

require __DIR__.'/auth.php';
