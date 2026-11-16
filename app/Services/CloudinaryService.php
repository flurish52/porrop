<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to Cloudinary's REST API directly (signed requests), so no extra composer package is needed.
 * We only ever store the returned public_id; URLs are built by App\Support\Cloudinary.
 */
class CloudinaryService
{
    /** @return string the public_id (includes the folder) */
    public function upload(UploadedFile $file, string $folder): string
    {
        [$cloud, $key, $secret] = $this->credentials();

        $params = ['folder' => $folder, 'timestamp' => time()];

        try {
            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("https://api.cloudinary.com/v1_1/{$cloud}/image/upload", $params + [
                    'api_key' => $key,
                    'signature' => $this->sign($params, $secret),
                ]);
        } catch (ConnectionException $e) {
            throw new CloudinaryException('Could not reach the image service. Please try again.', 0, $e);
        }

        $publicId = $response->json('public_id');

        if (! $response->successful() || ! $publicId) {
            Log::warning('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->json()]);
            throw new CloudinaryException($response->json('error.message') ?? 'Image upload failed.');
        }

        return $publicId;
    }

    /** Best effort: a failed delete must never block the vendor from removing the image in Porrop. */
    public function destroy(string $publicId): void
    {
        try {
            [$cloud, $key, $secret] = $this->credentials();

            $params = ['public_id' => $publicId, 'timestamp' => time()];

            Http::timeout(20)->asForm()->post("https://api.cloudinary.com/v1_1/{$cloud}/image/destroy", $params + [
                'api_key' => $key,
                'signature' => $this->sign($params, $secret),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Cloudinary delete failed', ['public_id' => $publicId, 'error' => $e->getMessage()]);
        }
    }

    /** Cloudinary signature: sorted "k=v&k=v" of the signed params, secret appended, SHA-1. */
    public function sign(array $params, string $secret): string
    {
        ksort($params);

        return sha1(urldecode(http_build_query($params)).$secret);
    }

    private function credentials(): array
    {
        $cloud = config('porrop.cloudinary_cloud');
        $key = config('porrop.cloudinary_key');
        $secret = config('porrop.cloudinary_secret');

        if (! $cloud || ! $key || ! $secret) {
            throw new CloudinaryException('Image uploads are not configured yet.');
        }

        return [$cloud, $key, $secret];
    }
}
