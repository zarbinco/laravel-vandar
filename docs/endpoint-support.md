# Endpoint Support Matrix

This package is unofficial and is not affiliated with Vandar. This matrix records the currently implemented package surface against the official endpoint facts used for this audit. It is intentionally conservative: unsupported endpoints are not advertised as supported.

Statuses:

- `supported`: implemented by a named package resource/method.
- `partially supported`: part of the endpoint group is implemented, but not every official endpoint in that group is available.
- `not implemented`: no named package resource/method is available.
- `future module`: intentionally left for a separate package module.
- `docs ambiguity`: official documentation is ambiguous or differs from the package's current endpoint contract.

Current notes:

- Customer cards are supported.
- Customer authentication and customer cash-in-code endpoints are supported.
- Subscription / Direct Debit endpoints are supported through `Vandar::subscriptions()`, `Vandar::subscription()`, and `Vandar::directDebit()`. Merchant or account activation may be required on Vandar's side.
- Ravand remains a future module and is not advertised as supported.
- Customer IBAN delete path ambiguity remains documented in the matrix below.
- Endpoint support means the SDK/client method exists. It does not mean this package provides payment, invoice, wallet, order, reconciliation, or logging workflows.

| Service area | Official endpoint / method | Package resource / method | Status | Notes |
| --- | --- | --- | --- | --- |
| Business | `GET /v2/business/:business` | `BusinessResource::info()` | supported | Uses main `https://api.vandar.io` base URL. |
| Business | `GET /v2/business/:business/iam` | `BusinessResource::users()` | supported |  |
| Balance | `GET /v2/business/:business/balance` | `BusinessResource::balance()` / `wallet()` | supported | `wallet()` is an alias for business balance. |
| Transactions | `GET /v3/business/:business/transaction` | `BusinessResource::transactions()` | supported | Query parameters are passed through. |
| IPG | `POST https://ipg.vandar.io/api/v4/send` | `IpgResource::send()` | supported | No bearer authorization header; IPG API key is sent in payload. |
| IPG | `GET https://ipg.vandar.io/v4/:token` | `IpgResource::redirectUrl()` / `gatewayUrl()` | supported | Returns the gateway URL string; no HTTP request is made. |
| IPG | `POST https://ipg.vandar.io/api/v4/transaction` | `IpgResource::transaction()` | supported | No bearer authorization header. |
| IPG | `POST https://ipg.vandar.io/api/v4/verify` | `IpgResource::verify()` / `verifyCallback()` | supported | `verifyCallback()` verifies after callback but does not mark application invoices as paid. |
| Refunds | `POST /v3/business/:business/transaction/:transaction_id/refund` | `RefundResource::create()` / `transaction()` | supported | Money-moving request is not retried automatically. |
| Settlements | `POST /v3/business/:business/settlement/store` | `SettlementResource::create()` / `store()` | supported | Money-moving request is not retried automatically. |
| Settlements | `GET /v4/business/:business/settlement/:track_id` | `SettlementResource::find()` / `show()` | supported |  |
| Settlements | `GET /v4/business/:business/settlement` | `SettlementResource::list()` / `all()` | supported | Query parameters are passed through. |
| Settlements | `DELETE /v4/business/:business/settlement/:track_id` | `SettlementResource::cancel()` | supported | Unsafe request is not retried automatically. |
| Settlements | `GET /v3/business/:business/settlement/banks` | `SettlementResource::banks()` | supported |  |
| Batch settlements | `POST https://batch.vandar.io/api/v2/business/:business/batches-settlement` | `BatchSettlementResource::create()` / `store()` | supported | Uses the `batch` base URL. |
| Batch settlements | `GET https://batch.vandar.io/api/v2/business/:business/batches` | `BatchSettlementResource::list()` / `all()` | supported | Uses the `batch` base URL. |
| Batch settlements | `GET https://batch.vandar.io/api/v2/business/:business/batch-settlements/:batch_id` | `BatchSettlementResource::details()` / `find()` / `show()` | supported | Uses the `batch` base URL. |
| Queued settlements | `POST /v3/business/:business/settlement/queued` | `QueuedSettlementResource::create()` / `store()` | supported | Money-moving request is not retried automatically. |
| Queued settlements | `GET /v3/business/:business/settlement/queued` | `QueuedSettlementResource::list()` / `all()` | supported |  |
| Queued settlements | `GET /v3/business/:business/settlement/queued/:id` | `QueuedSettlementResource::find()` / `show()` | supported |  |
| Queued settlements | `POST /v3/business/:business/settlement/queued/cancel` | `QueuedSettlementResource::cancel()` / `cancelById()` | supported | Unsafe request is not retried automatically. |
| Customers | `GET /v2/business/:business/customers` | `CustomerResource::list()` / `all()` | supported |  |
| Customers | `POST /v2/business/:business/customers` | `CustomerResource::create()` / `createIndividual()` / `createLegal()` | supported | Type helpers add `INDIVIDUAL` or `LEGAL` when missing. |
| Customers | `PUT /v2/business/:business/customers/:customer` | `CustomerResource::update()` / `updateIndividual()` / `updateLegal()` | supported |  |
| Customers | `DELETE /v2/business/:business/customers/:customer` | `CustomerResource::delete()` | supported |  |
| Customers | `GET /v2/business/:business/customers/:customer` | `CustomerResource::find()` / `show()` | supported |  |
| Customer fields | `GET /v2/business/:business/customers/fields` | `CustomerFieldResource::list()` | supported | Official docs may include a typo like `/v2/business/business/customers/fields`; package uses the business segment form. |
| Customer fields | `POST /v2/business/:business/customers/fields` | `CustomerFieldResource::create()` | supported |  |
| Customer fields | `PUT /v2/business/:business/customers/fields/:field_id` | `CustomerFieldResource::update()` | supported |  |
| Customer fields | `DELETE /v2/business/:business/customers/fields/:field_id` | `CustomerFieldResource::delete()` | supported |  |
| Customer fields | `GET /v2/business/:business/customers/fields/:field_id` | `CustomerFieldResource::find()` / `show()` | supported |  |
| Customer fields | Possible docs typo `/v2/business/business/customers/fields` | `CustomerFieldResource::*` | docs ambiguity | The package keeps the `:business` segment and does not hard-code `business`. |
| Customer wallet | `GET /v2/business/:business/customers/:customer/wallet` | `CustomerResource::walletBalance()` | supported |  |
| Customer wallet | `POST /v2/business/:business/customers/:customer/wallet/deposit` | `CustomerResource::walletDeposit()` | supported | Unsafe request is not retried automatically unless explicit rate-limit opt-in is enabled. Application wallet/ledger updates remain app-owned. |
| Customer wallet | `POST /v2/business/:business/customers/:customer/wallet/withdraw` | `CustomerResource::walletWithdraw()` | supported | Unsafe request is not retried automatically unless explicit rate-limit opt-in is enabled. Application wallet/ledger updates remain app-owned. |
| Customer transactions | `POST /v2/business/:business/customers/:customer/transactions` | `CustomerResource::transactions()` | supported |  |
| Customer authentication | `POST /v3/business/:business/customers/:customer/authentication/kyc` | `CustomerResource::authenticationKyc()` | supported | Customer authentication requires activation from Vandar support according to the official docs. |
| Customer authentication | `POST /v3/business/:business/customers/:customer/authentication/shahkar` | `CustomerResource::authenticationShahkar()` | supported | Customer-specific authentication endpoints are separate from the generic inquiry endpoints below. |
| Customer cash-in code | `GET /v3/business/:business/customers/:customer/cash-in-code` | `CustomerResource::cashInCode()` | supported | Customer cash-in-code is customer-scoped and different from business-level `AvandResource::code()` / `cashInCode()`. |
| Customer cash-in code | `DELETE /v3/business/:business/customers/:customer/cash-in-code/destroy` | `CustomerResource::deleteCashInCode()` | supported | Unsafe request is not retried automatically. |
| Customer cards | `POST /v3/business/:business/customers/:customer/cards` | `CardResource::create()` | supported | Customer card endpoints are documented by Vandar and covered by package contract tests. |
| Customer cards | `GET /v3/business/:business/customers/:customer/cards` | `CardResource::list()` | supported |  |
| Customer cards | `DELETE /v3/business/:business/customers/:customer/cards/:card` | `CardResource::delete()` | supported |  |
| Customer cards | `POST /v3/business/:business/customers/:customer/cards/:card/inquiry` | `CardResource::inquiry()` | supported |  |
| Customer cards | `POST /v3/business/:business/customers/:customer/cards/:card/set-default` | `CardResource::setDefault()` | supported |  |
| Customer cards | `POST /v3/business/:business/customers/:customer/cards/to-iban` | `CardResource::toIban()` | supported |  |
| Customer IBANs | `POST /v3/business/:business/customers/:customer/ibans` | `IbanResource::create()` | supported |  |
| Customer IBANs | `GET /v3/business/:business/customers/:customer/ibans` | `IbanResource::list()` / `all()` | supported |  |
| Customer IBANs | `DELETE /v3/business/:business/customers/:customer/ibans` | none matching exact path | docs ambiguity | Package currently deletes a specific IBAN at `/ibans/:iban`; official facts list delete without `:iban`. Current behavior is locked by contract tests and not changed here. |
| Customer IBANs | `DELETE /v3/business/:business/customers/:customer/ibans/:iban` | `IbanResource::delete()` | docs ambiguity | Package-supported path differs from the official facts supplied for this audit. |
| Customer IBANs | `POST /v3/business/:business/customers/:customer/ibans/:iban/inquiry` | `IbanResource::inquiry()` | supported |  |
| Customer IBANs | `POST /v3/business/:business/customers/:customer/ibans/:iban/set-default` | `IbanResource::setDefault()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/kyc` | `InquiryResource::kyc()` | supported | The package returns Vandar responses as-is, including any business mismatch `400` responses. |
| Inquiries | `POST /v3/business/:business/customers/inquiry/shahkar` | `InquiryResource::shahkar()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/nid` | `InquiryResource::nationalId()` / `nid()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/nid-image` | `InquiryResource::nationalIdImage()` / `nidImage()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/fida` | `InquiryResource::fida()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/postal-code` | `InquiryResource::postalCode()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/company-information` | `InquiryResource::companyInformation()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/company-signature` | `InquiryResource::companySignature()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/national-code-iban` | `InquiryResource::nationalCodeIban()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/match-national-code-iban` | `InquiryResource::matchNationalCodeIban()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/match-mobile-card` | `InquiryResource::matchMobileCard()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/iban` | `InquiryResource::iban()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/card` | `InquiryResource::card()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/card-to-iban` | `InquiryResource::cardToIban()` | supported |  |
| Avand / account | `GET /v3/business/:business/settlement/account/:iban/last-balance` | `AvandResource::lastBalance()` / `accountLastBalance()` | supported |  |
| Avand / account | `GET /v3/business/:business/settlement/account/statement` | `AvandResource::statement()` | supported |  |
| Avand / account | `GET /v3/business/:business/settlement/account/realtime-statement` | `AvandResource::realtimeStatement()` | supported |  |
| Avand / account | `POST /v3/business/:business/settlement/account/:iban/transaction/:tracking_code/label` | `AvandResource::addTransactionLabel()` | supported | Unsafe request is not retried automatically. |
| Avand / account | `DELETE /v3/business/:business/settlement/account/:iban/transaction/:tracking_code/label` | `AvandResource::removeTransactionLabel()` / `deleteTransactionLabel()` | supported | Unsafe request is not retried automatically. |
| Cash-in | `GET /v3/business/:business/cash-in/code` | `AvandResource::code()` / `cashInCode()` | supported |  |
| Cash-in | `GET /v3/business/:business/cash-in/pic/transactions` | `AvandResource::picTransactions()` | supported |  |
| Cash-in | `GET /v3/business/:business/cash-in/suspicious-payment` | `AvandResource::suspiciousPayments()` | supported |  |
| Cash-in | `POST /v3/business/:business/cash-in/suspicious-payment/:id` | `AvandResource::suspiciousPayment()` / `resolveSuspiciousPayment()` | supported | Unsafe request is not retried automatically. |
| Cash-in account | `GET /v3/business/:business/cash-in/account` | `AvandResource::account()` / `cashInAccount()` | supported |  |
| Cash-in account | `POST /v3/business/:business/cash-in/account/deposit` | `AvandResource::deposit()` | supported | Money-moving request is not retried automatically. |
| Cash-in account | `POST /v3/business/:business/cash-in/account/balance` | `AvandResource::balance()` | supported |  |
| Subscription / direct debit | `GET /v3/business/:business/subscription/banks/actives` | `SubscriptionResource::activeBanks()` / `banks()` | supported | Direct Debit / Subscription services may require merchant or account activation from Vandar. |
| Subscription / direct debit | `POST /v3/business/:business/subscription/authorization/store` | `SubscriptionResource::createAuthorization()` / `storeAuthorization()` | supported | Side-effect request is not retried automatically by default. |
| Subscription / direct debit | `GET https://subscription.vandar.io/authorizations/:token` | `SubscriptionResource::authorizationUrl()` / `authorizationRedirectUrl()` / `mandateUrl()` | supported | Returns a browser redirect URL string; no HTTP request is made. |
| Subscription / direct debit | `PATCH /v3/business/:business/subscription/authorization/:authorization_id/verify` | `SubscriptionResource::verifyAuthorization()` | supported | Side-effect request is not retried automatically by default. |
| Subscription / direct debit | `GET /v3/business/:business/subscription/authorization/:authorization_id/search` | `SubscriptionResource::searchAuthorization()` | supported |  |
| Subscription / direct debit | `GET /v3/business/:business/subscription/authorization` | `SubscriptionResource::listAuthorizations()` / `authorizations()` | supported |  |
| Subscription / direct debit | `GET /v3/business/:business/subscription/authorization/:authorization_id/calculation` | `SubscriptionResource::authorizationCalculation()` | supported |  |
| Subscription / direct debit | `DELETE /v3/business/:business/subscription/authorization/:authorization_id` | `SubscriptionResource::deleteAuthorization()` / `cancelAuthorization()` / `destroyAuthorization()` | supported | Side-effect request is not retried automatically by default. |
| Subscription / direct debit | `POST /v3/business/:business/subscription/withdrawal/store` | `SubscriptionResource::createWithdrawal()` / `storeWithdrawal()` | supported | Money-moving request is not retried automatically by default. |
| Subscription / direct debit | `GET /v3/business/:business/subscription/withdrawal/:withdrawal_id` | `SubscriptionResource::findWithdrawal()` / `showWithdrawal()` / `withdrawal()` | supported |  |
| Subscription / direct debit | `GET /v3/business/:business/subscription/withdrawal/track-id/:track_id` | `SubscriptionResource::withdrawalByTrackId()` | supported |  |
| Subscription / direct debit | `GET /v3/business/:business/subscription/withdrawal` | `SubscriptionResource::listWithdrawals()` / `withdrawals()` | supported |  |
| Subscription / direct debit | `GET /v3/business/:business/subscription/withdrawal?q=:authorization_id` | `SubscriptionResource::withdrawalsForAuthorization()` | supported | The authorization query value is redacted in package logs. |
| Subscription / direct debit | `PUT /v3/business/:business/subscription/withdrawal/:withdrawal_id` | `SubscriptionResource::updateWithdrawal()` | supported | Side-effect request is not retried automatically by default. |
| Subscription / direct debit | `POST /v3/business/:business/subscription/refunds` | `SubscriptionResource::createRefund()` / `storeRefund()` | supported | Side-effect request is not retried automatically by default. |
| Subscription / direct debit | `GET /v3/business/:business/subscription/refunds/:refund_id` | `SubscriptionResource::findRefund()` / `showRefund()` / `refund()` | supported |  |
| Subscription / direct debit | `GET /v3/business/:business/subscription/refunds` | `SubscriptionResource::listRefunds()` / `refunds()` | supported |  |
| Ravand | Official Ravand endpoint group | none | future module | Ravand has many endpoints and is intentionally left for a separate module. |
