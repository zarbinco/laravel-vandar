# Upgrade Guide

This guide highlights compatibility and safety notes for early `zarbinco/laravel-vandar` releases.

## From pre-1.0 versions

Pre-1.0 releases may add resources, tighten safety defaults, or expand documentation before the package reaches a stable API promise. Review `CHANGELOG.md`, rerun your package tests with `Vandar::fake()`, and re-check production payment flows before upgrading.

### IPG callback safety

`callbackSucceeded()` is deprecated for final payment decisions. It only reflects raw callback status and must not be used to mark invoices or orders as paid.

Use `callbackHasOkStatus()` when you only need to inspect the callback status. Use `verifyCallback()` before any paid-state transition, then compare the verified amount, factor number/order id, token, transaction id, and your local payment record. Update application payment records idempotently.

### Raw response body handling

`VandarResponse::body()` exposes the raw upstream response body for diagnostics. Do not log raw bodies in production.

`VandarResponse::toArray()` includes `redacted_body` instead of raw body, but parsed JSON and headers remain preserved for compatibility. Do not treat direct `toArray()` output as fully redacted. Use `redactedBody()` for raw body diagnostics and redact parsed JSON before logging.

Package exception context is redacted by the package. Direct response arrays in application logs are still your application's responsibility.

### Token refresh and rate-limit configuration

Token refresh is lock-protected when the configured cache store supports locks. Tune lock wait, refresh attempts, and retry sleep through the `VANDAR_TOKEN_*` environment values in `config/vandar.php`.

Rate-limit handling is conservative. Safe methods may respect `Retry-After`, but money-moving retries are disabled by default:

```env
VANDAR_RETRY_MONEY_MOVING_REQUESTS=false
```

Only enable money-moving retries when your application has explicit idempotency, duplicate-prevention, reconciliation, and operational controls.

### Endpoint support matrix

Review [docs/endpoint-support.md](docs/endpoint-support.md) after every upgrade. It records implemented resource methods, aliases, known documentation ambiguities, and future modules.

### Subscription / Direct Debit resource

Subscription / Direct Debit APIs are exposed through:

```text
Vandar::subscriptions()
Vandar::subscription()
Vandar::directDebit()
```

Direct Debit authorization, withdrawal, and refund identifiers are sensitive. Store them safely and avoid logging raw values.

### Static analysis and CI

The package includes Larastan/PHPStan static analysis and CI quality gates. Run these checks before publishing or upgrading application integrations:

```bash
composer validate --strict
composer format:test
composer analyse
composer test
composer audit:release
```
