<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Paystack split payments: each vendor gets a subaccount under OUR Paystack account (no Paystack account
 * needed on their side). With a subaccount on a payment, Paystack sends our commission to us and the rest
 * straight to the vendor's bank, so Porrop never holds vendor money.
 */
class SubaccountService
{
    /** @return list<array{name:string,code:string}> Nigerian banks, cached for a day. */
    public function banks(): array
    {
        return Cache::remember('paystack.banks.ng', now()->addDay(), function () {
            $seen = [];

            // Walk pages until one adds nothing new (also safe if Paystack ignores the page parameter).
            for ($page = 1; $page <= 10; $page++) {
                $body = $this->call('get', '/bank?'.http_build_query([
                    'country' => 'nigeria', 'currency' => 'NGN', 'perPage' => 100, 'page' => $page,
                ]));

                $added = 0;
                foreach (($body['data'] ?? []) as $b) {
                    if (! empty($b['code']) && ! empty($b['name']) && ! isset($seen[$b['code']]) && ($b['active'] ?? true)) {
                        $seen[$b['code']] = ['name' => $b['name'], 'code' => (string) $b['code']];
                        $added++;
                    }
                }

                if ($added === 0) {
                    break;
                }
            }

            $banks = array_values($seen);
            usort($banks, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

            if (! $banks) {
                throw new PaystackException('Could not load the bank list.');
            }

            return $banks;
        });
    }

    /** The name the bank has on this account number. Throws if the account cannot be found. */
    public function resolveAccountName(string $accountNumber, string $bankCode): string
    {
        $body = $this->call('get', '/bank/resolve?'.http_build_query([
            'account_number' => $accountNumber, 'bank_code' => $bankCode,
        ]));

        $name = $body['data']['account_name'] ?? null;

        if (! $name) {
            throw new PaystackException('We could not find that account. Check the bank and account number.');
        }

        return (string) $name;
    }

    /** Create the vendor's subaccount, or update it if they already have one. Returns the ACCT_ code. */
    public function sync(Vendor $vendor, string $bankCode, string $accountNumber): string
    {
        $payload = [
            'business_name' => $vendor->name,
            // The default percentage is never used: every payment sends an explicit flat commission.
            'percentage_charge' => (float) config('porrop.commission.rate', 10),
            'account_number' => $accountNumber,
            // Paystack's docs and API have used both names for the bank code; sending both is harmless.
            'settlement_bank' => $bankCode,
            'bank_code' => $bankCode,
        ];

        $body = $vendor->paystack_subaccount_code
            ? $this->call('put', '/subaccount/'.rawurlencode($vendor->paystack_subaccount_code), $payload)
            : $this->call('post', '/subaccount', $payload);

        $code = $body['data']['subaccount_code'] ?? $vendor->paystack_subaccount_code;

        if (! $code) {
            throw new PaystackException('Paystack did not return a subaccount code.');
        }

        return (string) $code;
    }

    /** Full decoded body, throws PaystackException with Paystack's own message on failure. */
    private function call(string $method, string $path, array $payload = []): array
    {
        $secret = config('paystack.secret_key');

        if (! $secret) {
            throw new PaystackException('Paystack secret key is not configured.');
        }

        try {
            $client = Http::withToken($secret)->acceptJson()->timeout(20)->baseUrl(rtrim(config('paystack.base_url'), '/'));

            $response = match ($method) {
                'get' => $client->get($path),
                'put' => $client->put($path, $payload),
                default => $client->post($path, $payload),
            };
        } catch (ConnectionException $e) {
            throw new PaystackException('Could not reach Paystack. Please try again.', 0, $e);
        }

        $body = $response->json() ?? [];

        if (! $response->successful() || ! ($body['status'] ?? false)) {
            throw new PaystackException($body['message'] ?? 'Paystack request failed.');
        }

        return $body;
    }
}
