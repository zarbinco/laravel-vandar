# Changelog

All notable changes to `zarbinco/laravel-vandar` will be documented in this file.

## Unreleased

### Added

- Added Laravel service provider, facade, publishable configuration, and package auto-discovery.
- Added configurable HTTP client with safe response handling.
- Added token refresh support with config, cache, and custom token stores.
- Added business, customer, card, IBAN, inquiry, IPG, refund, settlement, queued settlement, batch settlement, and Avand/Cash-in resources.
- Added safe response DTOs and exception mapping.
- Added testing utilities for faking Vandar API responses.
- Added release audit script and GitHub Actions workflow.
- Added official Vandar endpoint support matrix and contract tests for implemented resources.
- Added customer authentication and customer cash-in-code endpoint coverage.
- Added Subscription / Direct Debit API coverage.
- Added Larastan/PHPStan static analysis and CI quality gates.
- Added upgrade, production, and release checklist documentation.
- Added focused IPG callback safety tests for verify failures, unverified responses, mismatch risks, duplicate callbacks, deprecated status checks, and malformed verify responses.
- Added focused token refresh, cache token store, HTTP retry, timeout, and unsafe money-moving retry tests.
- Added a production-oriented Laravel payment integration example for safe application-side IPG usage.
- Added opt-in per-request token auto-refresh through `VANDAR_AUTO_REFRESH=false` by default.

### Changed

- Updated documentation for Packagist installation and auto-update setup.
- Improved package documentation, security guidance, and contribution workflow.
- Clarified the SDK/client boundary, application-owned payment records, callback verification, idempotency, reconciliation, logging policy, and multi-server token cache guidance.
- Clarified IPG callback controller guidance for application-owned verification, matching, idempotency, and duplicate callback handling.
- Clarified production token cache guidance, file-cache multi-server risk, and unknown-state handling for timed-out money-moving requests.
- Corrected roadmap documentation to list Subscription / Direct Debit as available and keep Ravand as future work.
- Updated testing documentation for Subscription / Direct Debit fake labels.
- Allowed package HTTP fakes to return raw string bodies for malformed response tests.
- Improved URL and payload handling for package logs and exception context.
- Polished the endpoint support matrix for customer card and subscription/direct-debit coverage.
- Polished package documentation and release readiness checks.
- Clarified safe logging documentation for `VandarResponse::toArray()`, `redactedBody()`, and redacted exception context.
- Raised the minimum Laravel Pint dev-tool version and adjusted lowest-dependency CI quality gates.
- Hardened the local and GitHub Actions quality gate around strict Composer validation, Pint, PHPStan/Larastan, PHPUnit, release audit, stable PHP matrix coverage, and lowest-dependency compatibility checks.

### Security

- Added recursive sensitive data redaction for tokens, API keys, identity data, card/IBAN data, settlement data, and payment identifiers.
- Added URL query and sensitive path segment sanitization for package logs.
- Improved redaction to mask standalone Iranian IBANs, card/PAN numbers, Iranian mobile numbers, and national-code-shaped values in text and nested redaction contexts without changing runtime API behavior.
- Disabled automatic retries for money-moving endpoints.
