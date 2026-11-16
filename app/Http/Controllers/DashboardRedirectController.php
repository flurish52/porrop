<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/** Breeze sends people to /dashboard after login. Send each kind of user to the right home. */
class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $vendor = $user->vendors()->orderBy('user_vendor.id')->first();

        if ($vendor) {
            return redirect()->route('vendor.dashboard', $vendor);
        }

        if ($user->is_platform_admin) {
            return redirect()->route('admin.payouts.index'); // replace with the admin dashboard once it exists
        }

        return redirect()->route('home');
    }
}
