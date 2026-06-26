# Production Checklist

Use this checklist when integrating the package into a production Laravel application.

- Keep `VANDAR_HTTP_VERIFY_SSL=true`
- Keep package logging disabled unless you have reviewed redaction behavior
- Use `redactedBody()` instead of raw response bodies in diagnostics
- Do not log `$response->toArray()` directly unless parsed JSON and headers have been redacted
- Use `$response->redactedBody()` for raw body diagnostics
- Rely on package-redacted exception context instead of raw response arrays
- Never trust an IPG callback alone
- Verify the callback token with `verifyCallback()` before marking anything as paid
- Match invoice/order amount, factor number/order id, token, and transaction id
- Update payment records idempotently
- Store Vandar transaction IDs, authorization IDs, withdrawal IDs, refund IDs, and track IDs safely
- Reconcile payments, refunds, settlements, queued settlements, batch settlements, and direct debit withdrawals
- Do not retry money-moving requests without application-level idempotency
- Respect Vandar rate limits and queue high-volume work
- Protect API keys, access tokens, refresh tokens, and authorization headers
- Avoid logging national code, mobile, IBAN, card, token, authorization, withdrawal, refund, transaction, settlement, and cash-in identifiers
- Keep config cached in production after environment changes
