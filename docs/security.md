# Security Notes

Treat this SDK as a transport layer. Your application remains responsible for authorization, persistence, retention, reconciliation, and operational controls.

## Secrets

Never commit real Vandar access tokens, refresh tokens, IPG API keys, callback URLs, or bearer values. Refresh tokens and IPG API keys are secrets.

Packagist and GitHub tokens used for package auto-update are maintainer secrets. Prefer the built-in GitHub and Packagist integration where possible, and never commit those tokens to source code, docs, tests, issues, or screenshots.

## Private Data

Customer identity, card, IBAN, mobile, postal, inquiry image, company signature, statement, settlement, cash-in, batch, refund, IPG callback, and verify response data are sensitive. Use fake placeholder values in tests and issue reports.

## Logging

Package logging is disabled by default. When enabled, the package redacts known sensitive payload, response, header, query, and dynamic path values. Your application logs, APM tools, queue payloads, audit logs, and exception reporters still need their own redaction policies.

IPG callback payloads and verify responses can include payment tokens, transaction identifiers, card hashes, CID values, amounts, and other payment data. Log them only after redaction.

## Money-Moving Requests

Payment, refund, settlement, queued settlement, batch settlement, Avand deposit, transaction label writes, and suspicious-payment resolution calls should be protected with authorization, idempotency, audit logging, duplicate-prevention, and reconciliation.

The package does not automatically retry known unsafe money-moving requests. SSL verification defaults to true and should not be disabled in production.
