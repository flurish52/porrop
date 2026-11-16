<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PaystackService
{
    /**
     * Start a transaction. $amountKobo is an integer in kobo.
     *
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initialize(
        string $email,
        int $amountKobo,
        string $reference,
        string $callbackUrl,
        array $metadata = [],
        ?string $subaccount = null,
        ?int $transactionChargeKobo = null,
        string $bearer = 'account',
    ): array {
        $payload = [
            'email' => $email,
            'amount' => $amountKobo,
            'reference' => $reference,
            'currency' => 'NGN',
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ];

        if ($subaccount) {
            $payload['subaccount'] = $subaccount;
            $payload['transaction_charge'] = $transactionChargeKobo;
            $payload['bearer'] = $bearer;
        }

        return $this->request('post', '/transaction/initialize', $payload);
    }
    /** Ask Paystack for the truth about a reference. Returns the `data` object. */
    public function verify(string $reference): array
    {
        return $this->request('get', '/transaction/verify/'.rawurlencode($reference));
    }

    /** Webhook authenticity: HMAC-SHA512 of the RAW body with the secret key. */
    public function validSignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('paystack.secret_key');

        if ($secret === '' || ! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $rawBody, $secret), $signature);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $secret = config('paystack.secret_key');
        if (! $secret) {
            throw new PaystackException('Paystack secret key is not configured.');
        }

        try {
            $client = Http::withToken($secret)
                ->acceptJson()
                ->timeout(20)
                ->baseUrl(rtrim(config('paystack.base_url'), '/'));

            $response = $method === 'get'
                ? $client->get($path)
                : $client->post($path, $payload);
        } catch (ConnectionException $e) {
            throw new PaystackException('Could not reach Paystack. Please try again.', 0, $e);
        }

        $body = $response->json();

        if (! $response->successful() || ! ($body['status'] ?? false)) {
            throw new PaystackException($body['message'] ?? 'Paystack request failed.');
        }

        return $body['data'] ?? [];
    }
}
