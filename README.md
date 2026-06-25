# Laravel Vandar SDK

This package is unofficial and is not affiliated with Vandar.

An unofficial Laravel SDK foundation for Vandar APIs. The package is designed to support IPG, settlements, customers, cards, IBANs, inquiries, direct debit, token refresh, and testing utilities over future phases.

## Installation

Install the package with Composer:

```bash
composer require zarbinco/laravel-vandar
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=vandar-config
```

## Basic Usage

```php
use Zarbinco\LaravelVandar\Facades\Vandar;

Vandar::name();
Vandar::baseUrl('api');
```

## HTTP Foundation

Phase 2 adds the generic HTTP and token-management foundation that future resources will use. It does not add named business, customer, card, IBAN, inquiry, IPG, settlement, or direct debit endpoint resources yet.

## Raw Requests

Use raw requests only for advanced/manual usage while named resources are introduced in future phases.

```php
use Zarbinco\LaravelVandar\Facades\Vandar;

$response = Vandar::raw()->get('api', '/v2/ping', auth: false);

if ($response->successful()) {
    $data = $response->json();
}
```

## Safe Response Handling

All HTTP calls return a `Zarbinco\LaravelVandar\DTO\VandarResponse` object.

```php
$response = Vandar::response(['message' => 'ok']);

$response->status();
$response->json();
$response->data();
$response->message();
$response->errors();
$response->trackId();
$response->successful();
$response->failed();
```

Call `throw()` when you want failed responses converted to package exceptions.

```php
$response->throw();
```

## Token Stores

Phase 2 supports these token store drivers:

- `config`: reads tokens from configuration and environment values. It is read-only at runtime.
- `cache`: reads initial tokens from configuration, then stores refreshed tokens in Laravel cache.
- `custom`: resolves your own implementation of `Zarbinco\LaravelVandar\Contracts\TokenStore`.

Example local placeholder configuration:

```env
VANDAR_TOKEN_STORE=cache
VANDAR_ACCESS_TOKEN=your-initial-access-token
VANDAR_REFRESH_TOKEN=your-initial-refresh-token
```

Use fake placeholder values in examples and tests. Never include real Vandar tokens in source control.

## Refresh Token Command

Refresh the configured Vandar access token:

```bash
php artisan vandar:refresh-token
```

The command never prints access tokens or refresh tokens.

## Exceptions

Failed response statuses are mapped by `VandarResponse::throw()`:

- `401`: `VandarAuthenticationException`
- `403`: `VandarAuthorizationException`
- `422`: `VandarValidationException`
- `429`: `VandarRateLimitException`
- `500-599`: `VandarServerException`
- Other failed statuses: `VandarRequestException`

Token failures use `VandarTokenException` and related subclasses. Exception context is redacted before storage.

## Configuration

The published configuration file is available at `config/vandar.php`.

It includes safe placeholders for:

- Business identifier configuration.
- Access and refresh token configuration.
- Token storage preferences.
- Base URLs for API, IPG, batch, and subscription services.
- HTTP timeout, SSL verification, and retry options.
- Logging options with sensitive data redaction enabled.

Never commit real Vandar tokens or private customer data to source control.

## Artisan

Display package information without exposing sensitive values:

```bash
php artisan vandar:about
```

The command prints package metadata, configured base URL keys, token store driver, token presence, cache encryption status, and logging status. It never prints access tokens or refresh tokens.

## Roadmap

- Phase 1: Foundation
- Phase 2: HTTP client + token system
- Phase 3: Business + customers
- Phase 4: Cards + IBANs
- Phase 5: Inquiry APIs
- Phase 6: IPG + refund
- Phase 7: Settlement + Avand + batch
- Phase 8: Public release polish

## Security

Never commit real Vandar tokens.

Do not log sensitive data. Use the package redaction utilities before storing or printing request, response, or diagnostic payloads that may include private values.

## Safe logging and redaction

Package logging is disabled by default. When enabled, request and response payloads are redacted before logging, and URLs are sanitized so sensitive query values are not written to logs.

Package commands never print access tokens or refresh tokens. Applications should still avoid logging sensitive business data outside this package.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
