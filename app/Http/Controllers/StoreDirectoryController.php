<?php

namespace App\Http\Controllers;

use App\Services\StoreDirectory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Public "Stores on Porrop" page. Not inside a store: no tenant is bound here. */
class StoreDirectoryController extends Controller
{
    public function __invoke(Request $request, StoreDirectory $directory): Response
    {
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 60));

        $stores = $directory->page($q ?: null);

        return Inertia::render('Stores/Index', [
            'stores' => $stores->items(),
            'total' => $stores->total(),
            'q' => $q,
            'prevUrl' => $stores->previousPageUrl(),
            'nextUrl' => $stores->nextPageUrl(),
            'page' => $stores->currentPage(),
            'lastPage' => $stores->lastPage(),
            'searchUrl' => route('stores.index'),
            'registerUrl' => route('vendor.register'),
        ]);
    }
}
