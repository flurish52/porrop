<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

#[Signature('sitemap:generate')]
#[Description('Generate public/sitemap.xml for the landing page, legal pages, active stores and products')]
class GenerateSitemap extends Command
{
    public function handle(): int
    {
        $sitemap = Sitemap::create()
            ->add(Url::create(route('home'))->setPriority(1.0))
            ->add(Url::create(route('legal.seller-terms'))->setPriority(0.2))
            ->add(Url::create(route('legal.privacy'))->setPriority(0.2))
            ->add(Url::create(route('legal.terms'))->setPriority(0.2))
            ->add(Url::create(route('legal.refunds'))->setPriority(0.2));

        $vendors = Vendor::where('status', 'active')->get();
        $sitemap->add($vendors);

        $products = Product::withoutGlobalScopes()
            ->with('vendor')
            ->whereHas('vendor', fn ($q) => $q->where('status', 'active'))
            ->where('status', true)
            ->get();
        $sitemap->add($products);

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated: '.$vendors->count().' stores, '.$products->count().' products.');

        return self::SUCCESS;
    }
}
