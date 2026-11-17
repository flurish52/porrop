<?php

namespace App\Http\Controllers;

use App\Services\StoreDirectory;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'commission' => config('porrop.commission'),
            'stores' => app(StoreDirectory::class)->featured(6),
        ]);
    }
}
