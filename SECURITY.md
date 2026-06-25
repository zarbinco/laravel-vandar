# Security Policy

Please report security issues privately to the package maintainer through the repository at https://github.com/zarbinco/laravel-vandar. Do not open public issues that include sensitive production data, credentials, or customer information.

Real Vandar tokens, national codes, card numbers, IBANs, mobile numbers, and other private values must never be committed to issues, pull requests, fixtures, or tests.

This package includes a recursive sensitive data redactor for safe diagnostic payload handling. Redaction is a defensive helper, not a replacement for careful token handling and secure logging policies in consuming applications.

Access tokens and refresh tokens must be treated as secrets. Refresh tokens are especially sensitive because they can be used to obtain new access tokens.

Package commands never print access tokens or refresh tokens. When package logging is enabled, request and response summaries are redacted before they are written.

A database token store is not included in Phase 2. Any future database-backed token store must encrypt token columns.

URLs may contain sensitive query parameters and are sanitized before package logging. Exception context is redacted by default. Users should avoid passing secrets in query strings when possible.

Customer payloads may contain sensitive personal data. The package redacts sensitive fields in package logging, but applications should avoid logging raw customer payloads outside this package.

Do not commit real customer data to tests or issue reports. Do not use real mobile numbers, national codes, card numbers, IBANs, or postal data in examples.

Card and IBAN payloads are sensitive. Package logging redacts `card_number`, `card`, `iban`, `account_number`, `mobile`, `national_code`, token, refresh token, and Authorization-like fields where configured.

Applications should avoid logging raw card or IBAN payloads outside this package. Do not use real card numbers, IBANs, mobile numbers, national codes, or postal codes in tests, issues, README examples, or zip review files.

Inquiry payloads and responses may contain highly sensitive identity, banking, image, and signature data. Do not log raw inquiry payloads or raw inquiry responses.

Package logging redacts known sensitive inquiry fields, including national-code, Fida, birth-date, postal-code, card, IBAN, mobile, image, and signature fields. Consuming applications must still redact their own logs and error reports.

Do not use real customer data in tests, issue reports, README examples, `REVIEW_NOTES.md`, or zip files. Treat identity images and company signature data as sensitive even when they are used only for troubleshooting.

IPG `api_key` values are secrets. Never commit `VANDAR_IPG_API_KEY`, never print it in command output, and never log raw payment payloads.

Payment tokens and transaction IDs should be treated as sensitive. Verification responses may include card/payment data and must be handled carefully.

Refund operations are money-moving and should be protected by app-level authorization, audit logging, idempotency controls, and clear operational review.

Package logging redacts known IPG/refund sensitive fields, but consuming applications must redact their own logs, exceptions, audit trails, and error reports too.

Settlement, batch settlement, and Avand/Cash-in payloads may contain sensitive banking and financial data. Never log raw settlement payloads or raw settlement responses.

Package logging redacts known settlement, batch, and cash-in fields, but applications must also redact their own logs, audit trails, exceptions, and error reports.

Money-moving operations must be protected by application-level authorization. Applications should use idempotency keys or internal duplicate-prevention around settlement creation and cancellation.

Applications should keep audit logs that do not expose sensitive banking data. Do not use real settlement, IBAN, account, payout, transfer, batch, or cash-in data in tests, issues, README examples, `REVIEW_NOTES.md`, or zip files.
