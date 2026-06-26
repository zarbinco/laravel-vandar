# Production Checklist

Use this checklist when integrating the package into a production Laravel application.

- Treat the package as an SDK/client, not a payment workflow or source of truth
- Keep application-side payment, invoice, wallet, order, ledger, and reconciliation records
- Keep `VANDAR_HTTP_VERIFY_SSL=true`
- Keep package logging disabled unless you have reviewed redaction behavior
- Define an application logging policy for Vandar payloads, identifiers, and exceptions
- Use `redactedBody()` instead of raw response bodies in diagnostics
- Do not log `$response->toArray()` directly unless parsed JSON and headers have been redacted
- Rely on package-redacted exception context instead of raw response arrays
- Never trust an IPG callback alone
- Verify the callback token with `verifyCallback()` before marking anything as paid
- Match invoice/order amount, factor number/order id, token, and transaction id
- Update payment records idempotently
- Handle duplicate callbacks idempotently
- Update wallet and ledger records idempotently when your application uses wallet-related endpoints
- Store Vandar transaction IDs, authorization IDs, withdrawal IDs, refund IDs, and track IDs safely
- Reconcile payments, refunds, settlements, queued settlements, batch settlements, and direct debit withdrawals
- Do not retry money-moving requests without application-level idempotency
- Respect Vandar rate limits and queue high-volume work
- Protect API keys, access tokens, refresh tokens, and authorization headers
- Use a shared cache such as Redis for `VANDAR_TOKEN_STORE=cache` in multi-server deployments
- Do not use file cache as the token store across multiple servers
- Treat timeouts and unknown responses from money-moving requests as unknown state before repeating them
- Avoid logging national code, mobile, IBAN, card, token, authorization, withdrawal, refund, transaction, settlement, and cash-in identifiers
- Keep config cached in production after environment changes
