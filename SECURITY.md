# Security Policy

This package is unofficial and is not affiliated with Vandar.

## Reporting Vulnerabilities

Please do not open public issues that include vulnerabilities, credentials, or private customer/payment data.

If GitHub security advisories are available for this repository, use a private advisory. If no private channel is configured, open a minimal public issue that does not disclose exploit details or sensitive data and ask the maintainers for a private reporting path.

Repository: https://github.com/zarbinco/laravel-vandar

## Secrets And Private Data

Never commit or paste real Vandar credentials or private data into issues, pull requests, fixtures, tests, logs, screenshots, zip files, or documentation examples.

Treat these values as sensitive:

- Access tokens and refresh tokens
- IPG API keys
- Authorization headers
- Payment tokens and transaction IDs
- Card numbers, IBANs, account numbers, CIDs, and reference numbers
- National codes, mobile numbers, postal codes, birth dates, identity images, and company signatures
- Statements, balances, settlement records, cash-in records, batch settlement records, labels, and suspicious-payment identifiers

Refresh tokens are especially sensitive because they can be used to obtain new access tokens. IPG API keys are secrets and must never be printed or logged.

## Logging

Package logging is disabled by default. When enabled, the package redacts known sensitive payload, response, header, query, and exact dynamic URL path values before writing request summaries.

This redaction is a defensive helper, not a complete application security policy. Consuming applications must still protect their own logs, audit trails, exception reports, queue payloads, APM traces, and support exports.

## Money-Moving Operations

Payment, refund, settlement, queued settlement, batch settlement, Avand deposit, transaction-label writes, and suspicious-payment resolution can move money or change business state.

The package does not automatically retry known unsafe money-moving requests. Applications must enforce authorization, idempotency, duplicate-prevention, audit logging, reconciliation, operational review, and least-privilege access.

## Transport Security

SSL verification defaults to true through `VANDAR_HTTP_VERIFY_SSL=true`. Do not disable SSL verification in production.

## Application Responsibility

This package does not create application routes, controllers, migrations, models, callback handlers, webhook handlers, scheduled jobs, or persistence tables. Applications are responsible for their own storage, retention, access control, workflows, and incident response.
