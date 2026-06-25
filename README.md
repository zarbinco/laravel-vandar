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

The command prints package metadata, configured base URL keys, token store driver, and logging status. It never prints access tokens or refresh tokens.

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

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
