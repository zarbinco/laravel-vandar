# Security Notes

Treat this package as a transport layer. It does not replace application-side payment, invoice, wallet, order, ledger, or reconciliation records. Your application remains responsible for authorization, persistence, retention, reconciliation, access control, logging policy, and operational review.

## API keys and tokens

Never commit, paste, screenshot, or print real Vandar credentials.

Sensitive credential values include:

- IPG API keys
- Access tokens
- Refresh tokens
- Authorization headers
- Payment tokens
- Packagist or GitHub tokens used by maintainers

Refresh tokens are especially sensitive because they can be exchanged for new access tokens. The token refresh command does not print access or refresh tokens.

In multi-server production deployments, use a shared cache such as Redis for the cache token store so refreshed tokens and refresh locks are shared across workers.

Do not use file cache as the token store across multiple servers. File cache is local to each server and cannot coordinate token refresh locks between workers on different hosts.

## Raw response bodies

`VandarResponse::body()` returns the raw upstream body. It is useful for debugging malformed JSON, HTML error pages, or unexpected upstream responses, but it may contain private customer, bank, payment, settlement, or direct debit data.

Do not log raw bodies in production. For raw upstream response diagnostics, prefer `$response->redactedBody()`.

```php
logger()->warning('Unexpected Vandar response body', [
    'status' => $response->status(),
    'content_type' => $response->contentType(),
    'body' => $response->redactedBody(),
]);
```

Safe:

- `$response->redactedBody()` for raw body diagnostics
- Package exception context, which is redacted before being attached

Unsafe unless manually redacted:

- `$response->body()`
- `$response->json()`
- `$response->headers()`
- `$response->toArray()`

`toArray()` avoids raw body leakage by using `redacted_body`, but parsed JSON and headers may still contain sensitive values. Redact parsed JSON and headers before logging direct response arrays.

Do not dump whole exception objects, traces, raw response arrays, or full banking payloads into production logs. When logging package exceptions, prefer selected fields and review even redacted context against your own retention policy.

```php
try {
    // Call Vandar through the SDK.
} catch (VandarRequestException $exception) {
    logger()->warning('Vandar request failed', [
        'status' => $exception->status(),
        'context' => $exception->context(),
    ]);
}
```

## Redacted package logging

Package logging is disabled by default. When enabled, the package redacts known sensitive payload, response, header, query, and dynamic path values before writing request summaries.

This redaction is a defensive helper. Application logs, audit trails, exception reporters, queue payloads, APM traces, support exports, and database records still need their own review. Debug logging should never include raw tokens, authorization headers, full banking payloads, or raw Vandar responses.

## IPG callbacks

Never trust an IPG callback alone. A callback can report an OK status without being sufficient for a final paid-state decision.

Use `callbackHasOkStatus()` only when you need to inspect the raw callback status. Use `verifyCallback()` before marking an invoice or order as paid, then compare amount, factor number/order id, token, transaction id, and your local payment record. Apply idempotency so duplicate callbacks cannot double-apply a payment.

## Identity payloads

National codes, mobile numbers, postal codes, birth dates, KYC payloads, identity images, company information, and company signatures are private data. Use fake placeholder values in tests, docs, issues, pull requests, and support examples.

## Card and IBAN data

Card numbers, card hashes, CIDs, IBANs, account numbers, reference numbers, and card-to-IBAN inquiry payloads should be treated as sensitive. Avoid logging them directly, and store only the values your application truly needs.

## Direct debit identifiers

Subscription / Direct Debit payloads can include bank, account, authorization, withdrawal, refund, track, and customer identifiers. Store them safely and do not log raw authorization tokens, withdrawal IDs, refund IDs, account numbers, IBANs, card numbers, national codes, or mobile numbers.

Money-moving direct debit withdrawal and refund calls are not retried automatically by default. Only enable money-moving retries when your application has idempotency and reconciliation controls.

Treat timeouts and unknown responses from money-moving requests as unknown state. Reconcile local records with Vandar before repeating payment verification, refund, settlement, withdrawal, deposit, or suspicious-payment resolution requests.

## SSL verification

SSL verification defaults to true through `VANDAR_HTTP_VERIFY_SSL=true`. Do not disable SSL verification in production.

## Fake and testing mode

Use `Vandar::fake()` and Laravel HTTP fakes for tests. Do not use real Vandar credentials, customer data, callback URLs, cards, IBANs, tokens, or transaction IDs in fixtures or assertions.

## Responsible disclosure

Do not open public issues that include vulnerabilities, credentials, or private customer/payment data. Follow the private reporting guidance in [SECURITY.md](../SECURITY.md).
