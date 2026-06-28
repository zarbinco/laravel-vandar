# Usage Guide

This package is unofficial and is not affiliated with Vandar. Examples use fake placeholder values only.

The package is pre-1.0. Review `CHANGELOG.md`, `UPGRADE.md`, and the endpoint support matrix before upgrading early releases.

## Package Boundary

`zarbinco/laravel-vandar` is a Laravel SDK/client for Vandar APIs. It builds requests, sends them, returns `VandarResponse`, refreshes tokens, redacts package logs, and provides test fakes.

It does not create a payment workflow, mark invoices/orders as paid, update wallets or ledgers, replace application payment records, or define routes, controllers, models, migrations, jobs, or reconciliation logic.

Your application must verify callbacks, handle idempotency, match amount, factor number/order id, token, and transaction id, update invoice/wallet/payment records, reconcile with Vandar, and decide what can be logged.

## Installation

The package is available on Packagist:

```bash
composer require zarbinco/laravel-vandar
```

Useful token refresh and rate-limit settings:

```env
VANDAR_TOKEN_LOCK_WAIT_SECONDS=5
VANDAR_TOKEN_REFRESH_ATTEMPTS=3
VANDAR_TOKEN_REFRESH_RETRY_SLEEP_MS=250
VANDAR_ACCESS_TOKEN_EXPIRES_AT=
VANDAR_AUTO_REFRESH=false
VANDAR_PERSIST_CONFIG_FALLBACK_TO_CACHE=false
VANDAR_RATE_LIMIT_AWARE=true
VANDAR_RESPECT_RETRY_AFTER=true
VANDAR_MAX_RETRY_AFTER_SECONDS=3
VANDAR_RETRY_SAFE_METHODS=true
VANDAR_RETRY_MONEY_MOVING_REQUESTS=false
```

Token refresh is lock-protected to reduce duplicate refresh calls when many requests hit near token expiry. Vandar also applies request limits; safe methods may be retried once on `429`, while money-moving requests are not retried by default.

`VANDAR_AUTO_REFRESH` defaults to `false` so existing applications keep their current token lifecycle without code changes. When explicitly set to `true`, authenticated requests may refresh an expiring access token before the API request is sent. Auto-refresh uses the configured refresh token, token store, lock, and retry settings; it requires a valid refresh token and a token store that can save refreshed tokens. It does not mean the package retries every failed API request. Production users can continue using a scheduled `vandar:refresh-token` command, or enable `VANDAR_AUTO_REFRESH=true` after testing the behavior with their token store.

Env/config fallback tokens remain supported, mainly as a bootstrap or development convenience. Set `VANDAR_ACCESS_TOKEN_EXPIRES_AT` to an ISO 8601 datetime or Unix timestamp when you know the real token expiry; otherwise the existing `VANDAR_ACCESS_TOKEN_TTL_SECONDS` fallback behavior is preserved. `VANDAR_PERSIST_CONFIG_FALLBACK_TO_CACHE` defaults to `false`; when enabled with the cache token store, config fallback tokens are written into cache on first read and future reads prefer the cached token state. Existing apps do not need code changes unless they want these opt-in behaviors.

In multi-server production deployments, back the `cache` token store with a shared cache such as Redis so refreshed tokens and locks are visible to every worker.

Do not use file cache as the token store across multiple servers. Each server would have its own local token state and refresh locks, which can cause stale tokens or duplicate refresh attempts.

## API Resources

Available resource entry points:

```text
Vandar::business()
Vandar::customers()
Vandar::cards()
Vandar::ibans()
Vandar::inquiries()
Vandar::ipg()
Vandar::refunds()
Vandar::settlements()
Vandar::queuedSettlements()
Vandar::batchSettlements()
Vandar::avand()
Vandar::cashIn()
Vandar::subscriptions()
Vandar::subscription()
Vandar::directDebit()
```

Ravand is not implemented and remains future work.

## Basic Request

```php
use Zarbinco\LaravelVandar\Facades\Vandar;

$balance = Vandar::business()->balance();

if ($balance->successful()) {
    $data = $balance->data();
}
```

## Business

```php
$balance = Vandar::business()->balance();
$transactions = Vandar::business()->transactions(['page' => 1]);
```

## Customers

```php
$customer = Vandar::customers()->createIndividual([
    'first_name' => 'Fake',
    'last_name' => 'User',
    'mobile' => 'fake-mobile',
    'individual_national_code' => 'fake-national-code',
]);

$found = Vandar::customers()->find('fake-customer-id');
```

Customer authentication and customer cash-in-code endpoints are customer-scoped. Authentication services may require activation by Vandar support. The package sends the request and returns `VandarResponse`; your application decides how to store, trust, or act on the result.

```php
$kyc = Vandar::customers()->authenticationKyc('fake-customer-id', [
    'national_code' => 'fake-national-code',
    'birth_date' => 'fake-birth-date',
]);

$shahkar = Vandar::customers()->authenticationShahkar('fake-customer-id', [
    'mobile' => 'fake-mobile',
    'national_code' => 'fake-national-code',
]);

$customerCashInCode = Vandar::customers()->cashInCode('fake-customer-id');

$deletedCustomerCashInCode = Vandar::customers()->deleteCashInCode('fake-customer-id');
```

Customer cash-in-code is different from business-level Avand cash-in code. Do not log sensitive identity payloads without redaction.

Customer wallet deposit/withdraw endpoints only send Vandar requests. Your application remains responsible for local wallet balances, ledger entries, duplicate prevention, and reconciliation.

## Cards And IBANs

```php
$card = Vandar::cards()->create('fake-customer-id', [
    'card' => 'fake-card',
    'track_id' => 'fake-track-id',
]);

$iban = Vandar::ibans()->create('fake-customer-id', [
    'iban' => 'fake-iban',
    'track_id' => 'fake-track-id',
]);
```

## Inquiries

```php
$shahkar = Vandar::inquiries()->shahkar([
    'mobile' => 'fake-mobile',
    'national_code' => 'fake-national-code',
]);

$ibanInquiry = Vandar::inquiries()->iban([
    'iban' => 'fake-iban',
    'track_id' => 'fake-track-id',
]);
```

## IPG And Refunds

```php
$payment = Vandar::ipg()->send([
    'amount' => 100000,
    'callback_url' => 'https://example.com/payments/callback',
]);

$redirectUrl = Vandar::ipg()->redirectUrl('fake-payment-token');
$verified = Vandar::ipg()->verify('fake-payment-token');

$refund = Vandar::refunds()->create('fake-transaction-id', [
    'amount' => 100000,
    'track_id' => 'fake-track-id',
]);
```

IPG callback status is only callback status. It is not final payment success, and this package does not mark invoices or orders as paid.

Wrong pattern:

```php
if (Vandar::ipg()->callbackSucceeded($request)) {
    // Do not mark as paid here.
}
```

Correct pattern:

```php
$result = Vandar::ipg()->verifyCallback($request);

if (! $result->verified()) {
    // Keep invoice pending/failed.
}

$response = $result->response();
$transactionId = $result->transactionId();
$amount = $result->amount();
$factorNumber = $result->factorNumber();

// Application must still compare expected invoice/order amount,
// factor number/order id, token, and transaction id,
// then update payment idempotently.
```

Your application must call verify after the callback and must handle idempotency, amount matching, invoice/order matching, transaction/token comparison, persistence, and reconciliation.

For a fuller application-side example with a local payments table and controller skeleton, see [laravel-payment-integration.md](laravel-payment-integration.md).

### Safe callback controller skeleton

This skeleton keeps the SDK boundary visible: verify before marking anything paid. The SDK returns Vandar responses only; the DB transaction belongs to your application.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Zarbinco\LaravelVandar\Exceptions\VandarIpgCallbackException;
use Zarbinco\LaravelVandar\Facades\Vandar;

public function callback(Request $request)
{
    try {
        $result = Vandar::ipg()->verifyCallback($request);
    } catch (VandarIpgCallbackException) {
        return response()->noContent();
    }

    if (! $result->verified()) {
        return response()->noContent();
    }

    DB::transaction(function () use ($result): void {
        // Load your local payment by stored Vandar token with a row lock.
        // Duplicate callbacks must be handled idempotently.
        // Compare amount, factorNumber/order id, token, and transaction id.
        // Normalize numeric strings before comparing amounts.
        // Update your own payment, invoice, order, or wallet records here.
    });

    return response()->noContent();
}
```

Do not treat the skeleton as package behavior. The package does not persist records, deduplicate callbacks, or decide whether a local invoice/order/wallet update is valid.

## Settlements

```php
$settlement = Vandar::settlements()->create([
    'iban' => 'fake-iban',
    'amount' => 100000,
    'track_id' => 'fake-track-id',
]);

$status = Vandar::settlements()->find('fake-track-id');
$queued = Vandar::queuedSettlements()->create([
    'iban' => 'fake-iban',
    'amount' => 100000,
]);

$batch = Vandar::batchSettlements()->create([
    'settlements' => [
        ['iban' => 'fake-iban', 'amount' => 100000],
    ],
]);
```

## Subscription / Direct Debit

Direct Debit / Subscription services may require merchant or account activation from Vandar. The authorization redirect URL is for sending the user to the Vandar or bank authorization flow. The package only sends requests and returns `VandarResponse`; your application must store authorization IDs, withdrawal IDs, track IDs, refund IDs, and statuses safely.

```php
$banks = Vandar::subscriptions()->activeBanks();

$authorization = Vandar::subscriptions()->createAuthorization([
    'track_id' => 'fake-track-id',
    'mobile' => 'fake-mobile',
    'national_code' => 'fake-national-code',
]);

$authorizationUrl = Vandar::subscriptions()->authorizationUrl(
    $authorization->string('token') ?? 'fake-authorization-token',
);

$verified = Vandar::subscriptions()->verifyAuthorization('fake-authorization-id', [
    'track_id' => 'fake-track-id',
]);

$withdrawal = Vandar::subscriptions()->createWithdrawal([
    'authorization_id' => 'fake-authorization-id',
    'amount' => 100000,
    'track_id' => 'fake-track-id',
]);

$refund = Vandar::subscriptions()->createRefund([
    'withdrawal_id' => 'fake-withdrawal-id',
    'amount' => 100000,
    'track_id' => 'fake-track-id',
]);
```

Money-moving direct debit withdrawal and refund calls are not retried automatically by default. Your application still needs idempotency, authorization, reconciliation, and duplicate-prevention. Do not log sensitive bank, account, card, IBAN, mobile, national-code, authorization, withdrawal, refund, or customer values without redaction.

## Avand/Cash-In

```php
$account = Vandar::avand()->account();
$balance = Vandar::avand()->balance(['track_id' => 'fake-track-id']);
$statement = Vandar::avand()->statement(['page' => 1]);
$code = Vandar::cashIn()->code();
```

All resource calls return `Zarbinco\LaravelVandar\DTO\VandarResponse`.

`json()` returns the parsed JSON array when available. `body()` keeps the raw response body for debugging, and `redactedBody()` returns a safer text body for logs. Use `jsonParseFailed()` to detect malformed JSON or unexpected upstream response bodies. Never log raw response bodies in production.

Do not log `$response->body()`, `$response->json()`, or `$response->toArray()` directly in production unless parsed JSON and headers have been redacted. `toArray()` includes `redacted_body` instead of raw body, but it preserves parsed JSON and headers for compatibility. Package exception context is redacted automatically.

```php
$response = Vandar::ipg()->verify($token);

if ($response->jsonParseFailed()) {
    logger()->warning('Unexpected Vandar response body', [
        'status' => $response->status(),
        'content_type' => $response->contentType(),
        'body' => $response->redactedBody(),
    ]);
}
```

Use response helpers to inspect rate limits:

```php
if ($response->rateLimited()) {
    $retryAfter = $response->retryAfter();
}
```

Applications should implement queue throttling and idempotency for high-volume or financial workflows. A retried financial operation is only safe when your application provides the idempotency guarantees.

Treat timeouts and unknown responses from money-moving requests as unknown state. Reconcile local records with Vandar before repeating refunds, settlements, withdrawals, deposits, or payment verification calls.

## Testing With Fakes

Use `Vandar::fake()` to test application code without real HTTP calls.

```php
Vandar::fake([
    'ipg.send' => [
        'status' => 200,
        'body' => [
            'status' => 1,
            'token' => 'fake-payment-token',
        ],
    ],
]);

$response = Vandar::ipg()->send([
    'amount' => 100000,
    'callback_url' => 'https://example.com/payments/callback',
]);

Vandar::assertSent('ipg.send');
Vandar::assertSentCount('ipg.send', 1);
Vandar::assertNotSent('ipg.verify');
```

URL-based fakes are also supported:

```php
Vandar::fake([
    'POST https://ipg.vandar.io/api/v4/send' => [
        'body' => ['token' => 'fake-payment-token'],
    ],
]);
```

See [testing.md](testing.md) for more testing examples.
