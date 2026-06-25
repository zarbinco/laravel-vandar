# Laravel Vandar SDK

[![Tests](https://github.com/zarbinco/laravel-vandar/actions/workflows/tests.yml/badge.svg)](https://github.com/zarbinco/laravel-vandar/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/zarbinco/laravel-vandar.svg)](https://packagist.org/packages/zarbinco/laravel-vandar)
[![License](https://img.shields.io/github/license/zarbinco/laravel-vandar.svg)](LICENSE.md)

This package is unofficial and is not affiliated with Vandar.

`zarbinco/laravel-vandar` is an SDK-first Laravel package for Vandar APIs. It provides a small HTTP foundation, token refresh support, named API resources, safe response objects, redacted package logging, and testing fakes.

## Features

- HTTP client and raw request helper
- Access/refresh token management
- Business APIs
- Customers and customer custom fields
- Customer cards and IBANs
- Inquiry APIs
- IPG payment send, redirect URL, transaction, and verify helpers
- Refunds
- Settlements, queued settlements, and batch settlements
- Avand/Cash-in account, statement, balance, label, code, and suspicious-payment helpers
- Offline testing fakes with `Vandar::fake()`

## Installation

The package is published on Packagist as `zarbinco/laravel-vandar`.

```bash
composer require zarbinco/laravel-vandar
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=vandar-config
```

## Packagist Auto-Update

Packagist auto-update is configured outside the package code. To keep Packagist synced with GitHub pushes, enable the GitHub Hook or auto-update integration from the `zarbinco/laravel-vandar` package page on Packagist.

If manual webhook setup is needed, use Packagist's official webhook and update instructions from the package maintain page. Never commit Packagist API tokens or GitHub tokens into this repository.

After pushing a tag such as `v0.1.0`, check the package page on Packagist to confirm the new version appears.

## Configuration

Common environment values:

```env
VANDAR_BUSINESS=fake-business
VANDAR_ACCESS_TOKEN=fake-access-token
VANDAR_REFRESH_TOKEN=fake-refresh-token
VANDAR_TOKEN_STORE=cache
VANDAR_IPG_API_KEY=fake-ipg-api-key
VANDAR_IPG_CALLBACK_URL=https://example.com/payments/callback
VANDAR_HTTP_VERIFY_SSL=true
VANDAR_LOGGING_ENABLED=false
```

Supported token stores are `config`, `cache`, and `custom`. The cache store encrypts cached token payloads by default.

Refresh the configured access token:

```bash
php artisan vandar:refresh-token
```

The refresh command does not print access tokens or refresh tokens.

## Usage

```php
use Zarbinco\LaravelVandar\Facades\Vandar;
```

### Business

```php
$balance = Vandar::business()->balance();
$transactions = Vandar::business()->transactions(['page' => 1]);
```

### Customers

```php
$customer = Vandar::customers()->createIndividual([
    'first_name' => 'Fake',
    'last_name' => 'User',
    'mobile' => 'fake-mobile',
    'individual_national_code' => 'fake-national-code',
]);
```

### Cards And IBANs

```php
$cards = Vandar::cards()->list('fake-customer-id');

$card = Vandar::cards()->create('fake-customer-id', [
    'card' => 'fake-card',
    'track_id' => 'fake-track-id',
]);

$ibans = Vandar::ibans()->list('fake-customer-id');

$iban = Vandar::ibans()->create('fake-customer-id', [
    'iban' => 'fake-iban',
    'track_id' => 'fake-track-id',
]);
```

### Inquiries

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

### IPG

```php
$payment = Vandar::ipg()->send([
    'amount' => 100000,
    'callback_url' => 'https://example.com/payments/callback',
]);

$redirectUrl = Vandar::ipg()->redirectUrl('fake-payment-token');
$verified = Vandar::ipg()->verify('fake-payment-token');
```

### Refunds

```php
$refund = Vandar::refunds()->create('fake-transaction-id', [
    'amount' => 100000,
    'track_id' => 'fake-track-id',
]);
```

### Settlements

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

### Avand/Cash-In

```php
$account = Vandar::avand()->account();
$balance = Vandar::avand()->balance(['track_id' => 'fake-track-id']);
$statement = Vandar::avand()->statement(['page' => 1]);
$code = Vandar::cashIn()->code();
```

More examples are available in [docs/usage.md](docs/usage.md).

## Response Handling

Every HTTP resource returns `Zarbinco\LaravelVandar\DTO\VandarResponse`.

```php
$response = Vandar::business()->balance();

$response->status();
$response->json();
$response->data();
$response->message();
$response->errors();
$response->trackId();
$response->successful();
$response->failed();
$response->throw();
```

`throw()` maps common failed statuses to package exceptions such as authentication, authorization, validation, rate-limit, server, and generic request exceptions.

## Safe Logging And Redaction

Package logging is disabled by default. When enabled, request and response summaries are redacted before logging. The package redacts known sensitive body, response, header, query, and exact dynamic URL path values.

Application logs, exception reporters, APM traces, queues, and audit logs are still your responsibility. Avoid logging raw tokens, API keys, card data, IBANs, identity data, statement data, settlement data, and payment payloads outside this package.

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
Vandar::assertSentCount('ipg.send', 1);
```

URL-based fakes are also supported:

```php
Vandar::fake([
    'POST https://ipg.vandar.io/api/v4/send' => [
        'body' => ['token' => 'fake-payment-token'],
    ],
]);
```

See [docs/testing.md](docs/testing.md).

## Security

Never commit real Vandar credentials or private customer/payment data. Treat access tokens, refresh tokens, IPG API keys, card numbers, IBANs, national codes, mobile numbers, postal data, images, signatures, statements, settlements, cash-in records, payment tokens, and transaction IDs as sensitive.

Money-moving endpoints are not automatically retried by the package. Your application should enforce authorization, idempotency, audit logging, reconciliation, and duplicate-prevention.

SSL verification defaults to true and should not be disabled in production.

Report vulnerabilities privately. See [SECURITY.md](SECURITY.md).

## Application Responsibility

This package does not create routes, controllers, migrations, views, models, webhook handlers, callback handlers, scheduled jobs, or application workflows. It does not persist Vandar responses in package-owned tables.

Your Laravel application owns persistence, authorization, operational review, reconciliation, UI, and domain-specific workflows.

## Roadmap

Not yet implemented:

- Direct debit
- Ravand, card issuing, and banking resources
- Optional encrypted database token store
- Additional typed response DTOs if needed

See [docs/roadmap.md](docs/roadmap.md).

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md). Contributions should include tests, pass Pint, use fake placeholder values, and keep the package app-agnostic.

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
