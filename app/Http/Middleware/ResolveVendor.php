<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every /store/{vendor:slug}/... route. Binds the vendor as 'currentVendor' so the
 * BelongsToVendor global scope limits all queries to this store, and so route('store.x') calls
 * inside the request do not need the slug passed again.
 *
 * Non-active stores are 404 to the public. Members of the store can still open it (to finish setup).
 */
class ResolveVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = $request->route('vendor');

        if (! $vendor instanceof Vendor) {
            $vendor = Vendor::where('slug', $vendor)->firstOrFail();
        }

        if (! $vendor->isActive()) {
            $user = $request->user();
            $isMember = $user && $user->vendors()->whereKey($vendor->id)->exists();

            abort_unless($isMember || ($user && $user->is_platform_admin), 404);
        }

        app()->instance('currentVendor', $vendor);
        $request->route()->forgetParameter('vendor');
        URL::defaults(['vendor' => $vendor->slug]);

        try {
            return $next($request);
        } finally {
            // Octane / queue workers / tests reuse the container: never leak the tenant.
            app()->forgetInstance('currentVendor');
        }
    }
}
