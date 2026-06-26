# Usage Guide

This package is unofficial and is not affiliated with Vandar. Examples use fake placeholder values only.

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
VANDAR_RATE_LIMIT_AWARE=true
VANDAR_RESPECT_RETRY_AFTER=true
VANDAR_MAX_RETRY_AFTER_SECONDS=3
VANDAR_RETRY_SAFE_METHODS=true
VANDAR_RETRY_MONEY_MOVING_REQUESTS=false
```

Token refresh is lock-protected to reduce duplicate refresh calls when many requests hit near token expiry. Vandar also applies request limits; safe methods may be retried once on `429`, while money-moving requests are not retried by default.

## Business

```php
use Zarbinco\LaravelVandar\Facades\Vandar;

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

Direct Debit / Subscription services may require activation from Vandar. The authorization redirect URL is for sending the user to the Vandar or bank authorization flow. The package only sends requests and returns `VandarResponse`; your application must store authorization IDs, withdrawal IDs, track IDs, refund IDs, and statuses safely.

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
