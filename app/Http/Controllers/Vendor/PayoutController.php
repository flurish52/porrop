<?php

namespace App\Http\Controllers\Vendor;

use App\Services\PaystackException;
use App\Services\SubaccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/** Where Paystack sends the vendor's money. Owner only (see the route). */
class PayoutController extends VendorAreaController
{
    public function __construct(private SubaccountService $subaccounts) {}

    public function edit(): Response
    {
        $vendor = app('currentVendor');

        try {
            $banks = $this->subaccounts->banks();
            $bankError = null;
        } catch (PaystackException $e) {
            $banks = [];
            $bankError = 'We could not load the bank list right now. Refresh in a minute.';
        }

        return $this->page('Vendor/Payout', [
            'details' => [
                'bank_code' => $vendor->bank_code ?? '',
                'bank_name' => $vendor->bank_name ?? '',
                'account_number' => $vendor->account_number ?? '',
                'account_name' => $vendor->account_name ?? '',
            ],
            'ready' => filled($vendor->paystack_subaccount_code),
            'banks' => $banks,
            'bankError' => $bankError,
            'verifyAccounts' => (bool) config('porrop.verify_bank_accounts'),
            'updateUrl' => route('vendor.payout.update', ['vendor' => $vendor]),
            'checkUrl' => route('vendor.payout.check', ['vendor' => $vendor]),
        ]);
    }

    /** Step 1 of saving: ask the bank whose account this is, so the vendor can confirm it is theirs. */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_code' => ['required', 'string', 'max:20'],
            'account_number' => ['required', 'regex:/^\d{10}$/'],
        ], [
            'bank_code.required' => 'Choose your bank.',
            'account_number.regex' => 'A Nigerian account number has exactly 10 digits.',
        ]);

        try {
            $bank = collect($this->subaccounts->banks())->firstWhere('code', $data['bank_code']);
            abort_unless($bank, 422, 'Choose your bank from the list.');

            $name = $this->subaccounts->resolveAccountName($data['account_number'], $data['bank_code']);
        } catch (PaystackException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['account_name' => $name, 'bank_name' => $bank['name']]);
    }

    public function update(Request $request): RedirectResponse
    {
        $verify = (bool) config('porrop.verify_bank_accounts');

        $data = $request->validate([
            'bank_code' => ['required', 'string', 'max:20'],
            'account_number' => ['required', 'regex:/^\d{10}$/'],
            'account_name' => [$verify ? 'nullable' : 'required', 'string', 'max:120'],
            'confirmed' => [$verify ? 'accepted' : 'nullable'],
        ], [
            'confirmed.accepted' => 'Please check the account name and confirm it is yours.',
            'bank_code.required' => 'Choose your bank.',
            'account_number.regex' => 'A Nigerian account number has exactly 10 digits.',
        ]);

        $vendor = app('currentVendor');

        try {
            $bank = collect($this->subaccounts->banks())->firstWhere('code', $data['bank_code']);

            if (! $bank) {
                throw ValidationException::withMessages(['bank_code' => 'Choose your bank from the list.']);
            }

            // The bank's own name for the account, so a typo can never send money to the wrong person.
            $name = $verify
                ? $this->subaccounts->resolveAccountName($data['account_number'], $data['bank_code'])
                : $data['account_name'];

            $code = $this->subaccounts->sync($vendor, $data['bank_code'], $data['account_number']);
        } catch (PaystackException $e) {
            throw ValidationException::withMessages(['account_number' => $e->getMessage()]);
        }

        $vendor->update([
            'bank_code' => $data['bank_code'],
            'bank_name' => $bank['name'],
            'account_number' => $data['account_number'],
            'account_name' => $name,
            'paystack_subaccount_code' => $code,
        ]);

        return back()->with('success', "Saved. Your sales will be paid into {$name} ({$bank['name']}).");
    }
}
