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

### Changed

- Updated documentation for Packagist installation and auto-update setup.
- Improved package documentation, security guidance, and contribution workflow.
- Improved URL and payload handling for package logs and exception context.
- Polished the endpoint support matrix for customer card and subscription/direct-debit coverage.

### Security

- Added recursive sensitive data redaction for tokens, API keys, identity data, card/IBAN data, settlement data, and payment identifiers.
- Added URL query and sensitive path segment sanitization for package logs.
- Disabled automatic retries for money-moving endpoints.
