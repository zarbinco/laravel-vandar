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
