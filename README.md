# Laravel Vandar

[![Tests](https://github.com/zarbinco/laravel-vandar/actions/workflows/tests.yml/badge.svg)](https://github.com/zarbinco/laravel-vandar/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/zarbinco/laravel-vandar.svg)](https://packagist.org/packages/zarbinco/laravel-vandar)
[![License](https://img.shields.io/github/license/zarbinco/laravel-vandar.svg)](LICENSE.md)

`zarbinco/laravel-vandar` is an unofficial Laravel SDK/client for Vandar APIs. It provides a small HTTP foundation, token refresh support, named API resources, safe response objects, redacted package logging, and offline testing fakes.

This package sends requests and returns Vandar responses. It does not create application payment workflows, persist responses, or mark invoices/orders as paid.

## Package Boundary

This package is an SDK/client only. It does:

- Build and send Vandar API requests from Laravel
- Return `VandarResponse` objects and mapped package exceptions
- Provide token refresh helpers, redacted package logging, and HTTP fakes

It does not:

- Mark invoices, orders, wallets, or subscriptions as paid or updated
- Replace application-side payment, invoice, wallet, order, or ledger records
- Provide routes, controllers, models, migrations, queues, reconciliation jobs, or a full payment workflow
- Decide your logging, retention, authorization, or operational review policy

Your Laravel application must verify callbacks, match amount, factor number/order id, token, and transaction id, update records idempotently, reconcile with Vandar, and choose what is safe to log.

## Features

- Laravel service provider, facade, config publishing, and package auto-discovery
- Configurable HTTP client with Vandar response helpers and exception mapping
- Access/refresh token management with config, cache, and custom token stores
- Business, customer, customer field, customer card, customer IBAN, inquiry, IPG, refund, settlement, queued settlement, batch settlement, Avand/Cash-in, and Subscription / Direct Debit APIs
- Conservative rate-limit handling with money-moving retries disabled by default
- Redacted package logging and redacted raw response body helpers
- Offline test fakes with `Vandar::fake()`

## Requirements

- PHP 8.2 or higher
- Laravel components compatible with Laravel 11, 12, or 13
- Composer 2

## Installation

Install the package from Packagist:

```bash
composer require zarbinco/laravel-vandar
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=vandar-config
```

## Configuration

Set the values your application needs. These names match `config/vandar.php`; examples use fake placeholders only.

```env
VANDAR_BUSINESS=
VANDAR_ACCESS_TOKEN=
VANDAR_REFRESH_TOKEN=
VANDAR_TOKEN_STORE=cache
VANDAR_API_URL=https://api.vandar.io
VANDAR_IPG_URL=https://ipg.vandar.io
VANDAR_BATCH_URL=https://batch.vandar.io
VANDAR_SUBSCRIPTION_URL=https://subscription.vandar.io
VANDAR_IPG_API_KEY=
VANDAR_IPG_CALLBACK_URL=https://example.com/payments/callback
VANDAR_HTTP_VERIFY_SSL=true
VANDAR_LOGGING_ENABLED=false
VANDAR_RATE_LIMIT_AWARE=true
VANDAR_RESPECT_RETRY_AFTER=true
VANDAR_MAX_RETRY_AFTER_SECONDS=3
VANDAR_RETRY_SAFE_METHODS=true
VANDAR_RETRY_MONEY_MOVING_REQUESTS=false
```

Supported token stores are `config`, `cache`, and `custom`. The cache store encrypts cached token payloads by default.

For multi-server production deployments, use a shared Laravel cache store such as Redis for `VANDAR_TOKEN_STORE=cache` so refreshed tokens and refresh locks are shared across workers.

Refresh the configured access token:

```bash
php artisan vandar:refresh-token
```

The refresh command does not print access tokens or refresh tokens.

## Quick Start

```php
use Zarbinco\LaravelVandar\Facades\Vandar;

$balance = Vandar::business()->balance();

$customer = Vandar::customers()->createIndividual([
    'first_name' => 'Fake',
    'last_name' => 'User',
    'mobile' => 'fake-mobile',
    'individual_national_code' => 'fake-national-code',
]);
```

Every HTTP resource returns `Zarbinco\LaravelVandar\DTO\VandarResponse`.

```php
$response = Vandar::business()->balance();

$response->status();
$response->json();
$response->data();
$response->message();
$response->errors();
$response->body();
$response->redactedBody();
$response->jsonParseFailed();
$response->successful();
$response->throw();
```

For raw upstream response diagnostics, prefer `$response->redactedBody()`.

Do not log `$response->toArray()` directly in production if parsed JSON or headers may contain sensitive values. `toArray()` preserves parsed JSON and headers for compatibility, while the raw body is exposed only as `redacted_body`. Package exception context is redacted automatically, but application logs should still avoid raw response arrays unless you have applied your own redaction.

## Safe IPG Payment Flow

IPG callback status is not final payment success. A callback can report `payment_status=OK`, but your application must still verify the payment before marking any invoice or order as paid.

```php
$payment = Vandar::ipg()->send([
    'amount' => 100000,
    'callback_url' => 'https://example.com/payments/callback',
]);

$redirectUrl = Vandar::ipg()->redirectUrl('fake-payment-token');
```

Do not use callback status alone for a paid-state transition:

```php
if (Vandar::ipg()->callbackSucceeded($request)) {
    // Do not mark as paid here.
}
```

Verify the callback first:

```php
$result = Vandar::ipg()->verifyCallback($request);

if (! $result->verified()) {
    // Keep invoice pending/failed.
}

$response = $result->response();
$transactionId = $result->transactionId();
$amount = $result->amount();
$factorNumber = $result->factorNumber();

// Application must compare expected invoice/order amount,
// factor number/order id, token, and transaction id,
// then update payment records idempotently.
```

Use `callbackHasOkStatus()` when you only need to inspect the raw callback status. Use `verifyCallback()` or `verify()` before any paid-state transition.

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
```

Subscription / Direct Debit aliases:

```text
Vandar::subscription()
Vandar::directDebit()
```

Subscription / Direct Debit services may require merchant or account activation on Vandar's side. The package exposes the client methods only; your application still stores mandates, authorization IDs, withdrawals, refunds, and reconciliation state.

See [docs/usage.md](docs/usage.md) for examples and [docs/endpoint-support.md](docs/endpoint-support.md) for the endpoint support matrix. Ravand is not implemented and remains future work.

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
Vandar::assertNotSent('ipg.verify');
```

See [docs/testing.md](docs/testing.md).

## Endpoint Coverage

See [docs/endpoint-support.md](docs/endpoint-support.md) for the current support matrix. The package intentionally separates implemented APIs from planned modules such as Ravand.

## Security

Never commit real Vandar credentials or private customer/payment data. Treat access tokens, refresh tokens, IPG API keys, card numbers, IBANs, national codes, mobile numbers, payment tokens, authorization IDs, withdrawal IDs, refund IDs, and transaction IDs as sensitive.

Package logging is disabled by default. When enabled, request and response summaries are redacted before logging. Application logs, exception reporters, queue payloads, APM traces, and support exports remain your responsibility.

SSL verification defaults to true and should not be disabled in production.

See [docs/security.md](docs/security.md), [docs/production-checklist.md](docs/production-checklist.md), and [SECURITY.md](SECURITY.md).

## Quality Checks

```bash
composer ci
```

`composer ci` runs strict Composer validation, Pint format checks, PHPStan/Larastan analysis, PHPUnit tests, and the release audit. Individual commands such as `composer format:test`, `composer analyse`, `composer test`, and `composer release:audit` remain available for focused local checks.

## Versioning

This package is pre-1.0. Review [CHANGELOG.md](CHANGELOG.md) and [UPGRADE.md](UPGRADE.md) before upgrading between early releases.

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md). Contributions should include tests, pass the quality checks, use fake placeholder values, and keep the package app-agnostic.

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
