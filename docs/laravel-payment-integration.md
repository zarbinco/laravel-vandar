# Laravel Payment Integration Example

This is an application-side example for using `zarbinco/laravel-vandar` in a Laravel payment flow. Adapt the model names, routes, statuses, and fields to your own application.

The package remains an SDK/client. It sends requests to Vandar and returns Vandar responses. Your application owns payment records, invoice/order/wallet updates, callback verification, idempotency, reconciliation, and logging policy.

## Safe Flow

1. Create a local payment record before redirecting the user.
2. Store the local payment as `pending`.
3. Call `Vandar::ipg()->send(...)`.
4. Store the returned Vandar gateway token on the local payment.
5. Redirect the user to `Vandar::ipg()->redirectUrl($token)`.
6. Receive the callback.
7. Do not mark invoices, orders, or wallets as paid from callback status alone.
8. Call `verifyCallback()` or `verify()`.
9. Compare the verified response with local payment data.
10. Update local records inside a DB transaction where useful.
11. Handle duplicate callbacks idempotently.
12. Treat timeouts or unknown verify results as `unknown`.
13. Reconcile before repeating money-moving operations.
14. Log only redacted diagnostics.

## Example Payments Table

Do not publish this as a package migration. These fields are examples for the consuming Laravel app.

| Field | Purpose |
| --- | --- |
| `id` | Local primary key. |
| `payable_type` / `payable_id` or `invoice_id` | Your invoice, order, wallet top-up, or payable record. |
| `gateway` | Example: `vandar`. |
| `amount` | Expected amount in the unit your app sends to Vandar. |
| `currency` | Optional, if your app stores multiple currencies. |
| `factor_number` | Local factor/order reference sent to Vandar. |
| `gateway_token` | Vandar payment token returned by IPG send. |
| `transaction_id` | Verified Vandar transaction/reference id. |
| `status` | `pending`, `callback_received`, `verified`, `failed`, or `unknown`. |
| `verified_at` | When the app accepted a verified payment. |
| `failed_at` | When the app marked the local payment failed. |
| `failure_reason` | Short internal failure reason. |
| `raw_callback_redacted` | Optional redacted callback diagnostics. |
| `raw_verify_redacted` | Optional `redactedBody()` output from verify. |
| `created_at` / `updated_at` | Normal timestamps. |

Store only the values your application needs. Avoid storing raw callback payloads or raw Vandar responses.

## Controller Skeleton

This is application code, not package behavior. Keep remote HTTP calls, database transactions, and local record updates shaped around your own app.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Exceptions\VandarIpgCallbackException;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;
use Zarbinco\LaravelVandar\Facades\Vandar;

final class VandarPaymentController
{
    public function start(Invoice $invoice)
    {
        $payment = Payment::create([
            'payable_type' => $invoice->getMorphClass(),
            'payable_id' => $invoice->id,
            'gateway' => 'vandar',
            'amount' => $invoice->amount,
            'currency' => 'IRR',
            'factor_number' => (string) $invoice->number,
            'status' => 'pending',
        ]);

        try {
            $response = Vandar::ipg()->send([
                'amount' => $payment->amount,
                'factorNumber' => $payment->factor_number,
                'callback_url' => route('payments.vandar.callback'),
            ]);
        } catch (VandarRequestException $exception) {
            $payment->update([
                'status' => 'unknown',
                'failure_reason' => 'ipg_send_unknown',
            ]);

            Log::warning('Vandar IPG send failed', [
                'payment_id' => $payment->id,
                'status' => $exception->status(),
                'context' => $exception->context(),
            ]);

            return redirect()->route('invoices.show', $invoice);
        }

        $token = $response->string('token');

        if ($token === null) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => 'missing_gateway_token',
                'raw_verify_redacted' => $response->redactedBody(),
            ]);

            return redirect()->route('invoices.show', $invoice);
        }

        $payment->update(['gateway_token' => $token]);

        return redirect()->away(Vandar::ipg()->redirectUrl($token));
    }

    public function callback(Request $request)
    {
        $callbackToken = $request->string('token')->toString();

        try {
            $result = Vandar::ipg()->verifyCallback($request);
        } catch (VandarIpgCallbackException) {
            return response()->noContent();
        } catch (VandarRequestException $exception) {
            $this->markVerifyUnknown($callbackToken, $exception);

            return response()->noContent();
        }

        DB::transaction(function () use ($request, $result): void {
            $payment = Payment::query()
                ->where('gateway', 'vandar')
                ->where('gateway_token', $result->token())
                ->lockForUpdate()
                ->first();

            if ($payment === null || $payment->status === 'verified') {
                return;
            }

            $payment->status = 'callback_received';
            $payment->raw_callback_redacted = json_encode([
                'payment_status' => $request->input('payment_status'),
                'token' => '[redacted]',
            ]);
            $payment->raw_verify_redacted = $result->response()->redactedBody();

            $expectedAmount = $this->normalizeAmount($payment->amount);
            $verifiedAmount = $this->normalizeAmount($result->amount());
            $transactionId = $result->transactionId();
            $factorNumber = $result->factorNumber();

            $matches = $result->verified()
                && $expectedAmount !== null
                && $verifiedAmount === $expectedAmount
                && hash_equals((string) $payment->factor_number, (string) $factorNumber)
                && hash_equals((string) $payment->gateway_token, $result->token())
                && $transactionId !== null
                && ($payment->transaction_id === null || hash_equals((string) $payment->transaction_id, $transactionId));

            if (! $matches) {
                $payment->status = 'failed';
                $payment->failed_at = now();
                $payment->failure_reason = 'verify_mismatch';
                $payment->save();

                return;
            }

            $payment->status = 'verified';
            $payment->transaction_id = $transactionId;
            $payment->verified_at = now();
            $payment->failure_reason = null;
            $payment->save();

            $invoice = $payment->payable()->lockForUpdate()->first();

            if ($invoice !== null && ! $invoice->is_paid) {
                $invoice->markAsPaid();
            }
        });

        return response()->noContent();
    }

    private function markVerifyUnknown(string $token, VandarRequestException $exception): void
    {
        Payment::query()
            ->where('gateway', 'vandar')
            ->where('gateway_token', $token)
            ->whereNot('status', 'verified')
            ->update([
                'status' => 'unknown',
                'failure_reason' => 'verify_unknown',
            ]);

        Log::warning('Vandar verify result unknown', [
            'gateway' => 'vandar',
            'status' => $exception->status(),
            'context' => $exception->context(),
        ]);
    }

    private function normalizeAmount(int|string|null $amount): ?string
    {
        return is_numeric($amount) ? (string) (int) $amount : null;
    }
}
```

Notes:

- `markAsPaid()` is your application method, not package behavior.
- The local payment row is locked before status changes so duplicate callbacks cannot double-apply updates.
- `raw_verify_redacted` uses `redactedBody()`. Do not store `$response->body()`, `$response->json()`, or full exception objects raw.
- If verify times out or the response is unknown, keep the payment in `unknown` and reconcile before trying financial actions again.

## What This Package Does Not Do

- It does not create payment tables.
- It does not mark invoices or orders paid.
- It does not update wallets or ledgers.
- It does not guarantee callback authenticity from callback status alone.
- It does not replace app-level reconciliation.
- It does not decide what your application can log or retain.

## Common Mistakes

- Marking an invoice paid from callback status alone.
- Trusting the amount from the original local request without checking the verified response.
- Not checking `factorNumber` or your local order reference.
- Not comparing the callback token and verified transaction id with local records.
- Not handling duplicate callbacks idempotently.
- Logging raw Vandar responses, raw callback payloads, or full exception objects.
- Retrying money-moving requests after a timeout without reconciliation.
- Using file cache for token storage in multi-server production.
