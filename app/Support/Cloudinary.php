<?php

namespace App\Support;

class Cloudinary
{
    /**
     * Build a delivery URL from a stored public id. Returns null when there is no id or no cloud name,
     * so callers can fall back to a placeholder instead of rendering a broken image.
     */
    public static function url(?string $publicId, string $transform = 'f_auto,q_auto,w_600'): ?string
    {
        $cloud = config('porrop.cloudinary_cloud');

        if (! $publicId || ! $cloud) {
            return null;
        }

        return "https://res.cloudinary.com/{$cloud}/image/upload/{$transform}/{$publicId}";
    }
}
