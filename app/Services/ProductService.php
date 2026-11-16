<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    /** @param array{name:string,description:?string,status:string,variants:array} $data */
    public function create(Vendor $vendor, array $data): Product
    {
        return DB::transaction(function () use ($vendor, $data) {
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($vendor, $data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);

            $this->syncVariants($vendor, $product, $data['variants']);

            return $product;
        });
    }

    public function update(Vendor $vendor, Product $product, array $data): Product
    {
        return DB::transaction(function () use ($vendor, $product, $data) {
            // The slug is deliberately NOT regenerated: links people already shared must keep working.
            $product->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);

            $this->syncVariants($vendor, $product, $data['variants']);

            return $product;
        });
    }

    /**
     * Rows with an id update that variant (it must belong to THIS product), rows without create one,
     * and variants missing from the payload are soft-deleted (old orders keep their snapshots).
     */
    private function syncVariants(Vendor $vendor, Product $product, array $rows): void
    {
        $existing = $product->variants()->get()->keyBy('id');
        $single = count($rows) === 1;
        $keep = [];

        foreach ($rows as $i => $row) {
            $name = trim((string) ($row['variant_name'] ?? ''));
            if ($name === '') {
                $name = 'Default'; // only possible when there is a single variant (validated)
            }

            $attrs = [
                'variant_name' => $name,
                'price' => $row['price'],
                'stock_quantity' => (int) $row['stock_quantity'],
            ];

            if (! empty($row['id'])) {
                $variant = $existing->get((int) $row['id']);

                if (! $variant) {
                    throw ValidationException::withMessages(["variants.$i.id" => 'That option no longer exists. Refresh and try again.']);
                }

                $variant->update($attrs);
                $keep[] = $variant->id;
            } else {
                $variant = ProductVariant::create($attrs + [
                    'vendor_id' => $vendor->id,
                    'product_id' => $product->id,
                    'sku' => $this->newSku($vendor, $product),
                ]);
                $keep[] = $variant->id;
            }
        }

        $product->variants()->whereNotIn('id', $keep)->delete();
    }

    private function uniqueSlug(Vendor $vendor, string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $n = 2;

        // withTrashed: the unique index still counts soft-deleted rows.
        while (Product::withoutGlobalScope('vendor')->withTrashed()->where('vendor_id', $vendor->id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    private function newSku(Vendor $vendor, Product $product): string
    {
        do {
            $sku = Str::upper(Str::limit(Str::slug($product->name, ''), 4, '')).'-'.Str::upper(Str::random(6));
        } while (ProductVariant::withoutGlobalScope('vendor')->withTrashed()->where('vendor_id', $vendor->id)->where('sku', $sku)->exists());

        return $sku;
    }
}
