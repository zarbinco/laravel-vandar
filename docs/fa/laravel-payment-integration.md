# نمونه‌ی اتصال پرداخت در Laravel

[فارسی](laravel-payment-integration.md) | [English](../en/laravel-payment-integration.md)

این صفحه یک نمونه‌ی application-side برای استفاده از `zarbinco/laravel-vandar` در flow پرداخت Laravel است. اسم modelها، routeها، statusها و fieldها را باید با ساختار اپلیکیشن خودتان هماهنگ کنید.

پکیج همچنان فقط SDK/client است. یعنی request را به Vandar می‌فرستد و response را برمی‌گرداند. رکورد پرداخت، به‌روزرسانی invoice/order/wallet، verify کردن callback، idempotency، reconciliation و سیاست logging با اپلیکیشن شماست.

## flow امن پیشنهادی

1. قبل از redirect کاربر، یک رکورد payment داخلی بسازید.
2. status داخلی را `pending` بگذارید.
3. `Vandar::ipg()->send(...)` را صدا بزنید.
4. token برگشتی Vandar را روی payment داخلی ذخیره کنید.
5. کاربر را به `Vandar::ipg()->redirectUrl($token)` redirect کنید.
6. callback را دریافت کنید.
7. فقط با callback status هیچ invoice، order یا wallet را paid نکنید.
8. `verifyCallback()` یا `verify()` را اجرا کنید.
9. response verify شده را با payment داخلی مقایسه کنید.
10. updateهای حساس را داخل DB transaction انجام دهید.
11. duplicate callbackها را idempotent مدیریت کنید.
12. timeout یا نتیجه‌ی نامشخص verify را `unknown` نگه دارید.
13. قبل از تکرار عملیات مالی reconciliation انجام دهید.
14. فقط diagnostics redact شده را لاگ کنید.

## نمونه‌ی جدول payments

این migration مربوط به پکیج نیست. فقط ایده‌ای برای اپلیکیشن مصرف‌کننده است.

| field | کاربرد |
| --- | --- |
| `id` | کلید داخلی. |
| `payable_type` / `payable_id` یا `invoice_id` | invoice، order، wallet top-up یا payable داخلی شما. |
| `gateway` | مثلا `vandar`. |
| `amount` | مبلغی که اپلیکیشن به Vandar می‌فرستد. |
| `factor_number` | reference داخلی که به Vandar ارسال می‌شود. |
| `gateway_token` | token برگشتی IPG send. |
| `transaction_id` | transaction/reference id تاییدشده. |
| `status` | مثل `pending`، `callback_received`، `verified`، `failed` یا `unknown`. |
| `raw_callback_redacted` | callback diagnostics بعد از redaction. |
| `raw_verify_redacted` | خروجی `redactedBody()` از verify. |

فقط چیزهایی را ذخیره کنید که واقعا لازم دارید. callback خام یا response خام Vandar را نگه ندارید مگر اینکه کاملا redacted شده باشد.

## controller skeleton

این کد فقط نمونه‌ی اپلیکیشن است، نه رفتار پکیج.

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

## نکته‌ها

- `markAsPaid()` method اپلیکیشن شماست، نه پکیج.
- رکورد payment با `lockForUpdate()` قفل می‌شود تا duplicate callback دوبار اثر نگذارد.
- `raw_verify_redacted` از `redactedBody()` استفاده می‌کند. response خام، JSON خام یا exception کامل را ذخیره نکنید.
- اگر verify timeout شد یا نتیجه نامشخص بود، payment را `unknown` نگه دارید و قبل از عملیات مالی بعدی reconciliation انجام دهید.

## این پکیج چه کاری نمی‌کند؟

- payment table نمی‌سازد.
- invoice یا order را paid نمی‌کند.
- wallet یا ledger را update نمی‌کند.
- callback status را به‌تنهایی معتبر فرض نمی‌کند.
- جایگزین reconciliation اپلیکیشن نیست.
- تصمیم نمی‌گیرد چه چیزی را می‌توانید لاگ یا نگهداری کنید.

## اشتباه‌های رایج

- paid کردن invoice فقط با callback status.
- مقایسه نکردن مبلغ verify شده با مبلغ داخلی.
- چک نکردن `factorNumber` یا order reference.
- مقایسه نکردن callback token و transaction id با رکورد داخلی.
- idempotent نبودن duplicate callback.
- لاگ کردن response خام Vandar یا payload خام callback.
- retry عملیات مالی بعد از timeout بدون reconciliation.
- استفاده از file cache برای token storage در production چندسروری.
