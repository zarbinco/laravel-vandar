# Security Policy

Please report security issues privately to the package maintainer through the repository at https://github.com/zarbinco/laravel-vandar. Do not open public issues that include sensitive production data, credentials, or customer information.

Real Vandar tokens, national codes, card numbers, IBANs, mobile numbers, and other private values must never be committed to issues, pull requests, fixtures, or tests.

This package includes a recursive sensitive data redactor for safe diagnostic payload handling. Redaction is a defensive helper, not a replacement for careful token handling and secure logging policies in consuming applications.
