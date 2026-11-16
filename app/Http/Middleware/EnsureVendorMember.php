<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use after ResolveVendor. `vendor.member` = any member, `vendor.member:owner` = owners only.
 */
class EnsureVendorMember
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        $vendor = app('currentVendor');

        $query = $user->vendors()->whereKey($vendor->id);
        if ($role) {
            $query->wherePivot('role', $role);
        }

        abort_unless($query->exists(), 403);

        return $next($request);
    }
}
