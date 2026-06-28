# Laravel Vandar Documentation

[English](README.md) | [فارسی](../fa/README.md)

`zarbinco/laravel-vandar` is an unofficial Laravel SDK/client for Vandar APIs. It helps Laravel applications send requests to Vandar, receive structured responses, manage tokens, redact package logs, and test integrations offline.

The package is not a complete payment workflow. Your application remains responsible for payment records, callback verification, invoice/order updates, wallet updates, reconciliation, idempotency, authorization, and logging policy.

## Start Here

- [Installation and configuration](usage.md#installation)
- [Usage guide](usage.md)
- [Security notes](security.md)
- [Testing with fakes](testing.md)
- [Endpoint support matrix](endpoint-support.md)
- [Laravel payment integration guide](laravel-payment-integration.md)
- [Production checklist](production-checklist.md)
- [Release checklist](release-checklist.md)
- [Roadmap](roadmap.md)

## Important Defaults

- Package logging is disabled by default.
- Redaction has been improved, but it remains defensive best effort.
- Do not log raw sensitive API responses directly in production.
- `VANDAR_AUTO_REFRESH=false` keeps per-request token auto-refresh opt-in.
- Auto-refresh does not retry every failed API request.
- Production token handling should use the cache token store plus the scheduled `vandar:refresh-token` command, or the cache token store plus explicitly enabled auto-refresh after testing.
- `VANDAR_ACCESS_TOKEN_EXPIRES_AT` can provide a real expiry timestamp for config fallback tokens.
- `VANDAR_PERSIST_CONFIG_FALLBACK_TO_CACHE=false` keeps config fallback persistence opt-in.
- `VANDAR_IBAN_DELETE_ENDPOINT_STYLE=path` keeps the existing IBAN delete endpoint behavior.
- `VANDAR_IBAN_DELETE_ENDPOINT_STYLE=documented` is opt-in and must be manually verified against the real Vandar API before production use.

## Package Boundary

This package sends Vandar API requests and returns Vandar responses. It does not create Laravel routes, controllers, migrations, models, payment tables, wallet ledgers, reconciliation jobs, or final paid-state decisions.
