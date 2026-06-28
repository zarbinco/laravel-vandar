# جدول endpointهای پشتیبانی‌شده

[فارسی](endpoint-support.md) | [English](../en/endpoint-support.md)

این پکیج غیررسمی است و وابستگی رسمی به Vandar ندارد. این صفحه نشان می‌دهد سطح فعلی پکیج در برابر endpointهای شناخته‌شده چیست. معنی «supported» این است که method یا resource متناظر در SDK وجود دارد؛ به معنی ساخت workflow پرداخت، invoice، کیف پول، reconciliation یا logging policy در اپلیکیشن شما نیست.

## وضعیت‌ها

- `supported`: resource/method در پکیج وجود دارد.
- `partially supported`: بخشی از آن گروه endpoint پیاده‌سازی شده است.
- `not implemented`: method مشخصی در پکیج ندارد.
- `future module`: عمدا برای ماژول جداگانه یا کار آینده نگه داشته شده است.
- `docs ambiguity`: مستندات رسمی یا شکل endpoint ابهام دارد.

## نکات فعلی

- Customer cards پشتیبانی می‌شود.
- Customer authentication و customer cash-in-code پشتیبانی می‌شود.
- Subscription / Direct Debit از طریق `Vandar::subscriptions()`، `Vandar::subscription()` و `Vandar::directDebit()` در دسترس است. ممکن است فعال‌سازی حساب یا merchant از سمت Vandar لازم باشد.
- Ravand فعلا future module است و به‌عنوان قابلیت موجود معرفی نمی‌شود.
- ابهام مسیر delete برای Customer IBAN در جدول آمده است. رفتار پیش‌فرض پکیج همان `/ibans/:iban` است و حالت documented اختیاری است.

| بخش | endpoint یا method | method پکیج | وضعیت | توضیح |
| --- | --- | --- | --- | --- |
| Business | `GET /v2/business/:business` | `BusinessResource::info()` | supported | از base URL اصلی استفاده می‌کند. |
| Business | `GET /v2/business/:business/iam` | `BusinessResource::users()` | supported |  |
| Balance | `GET /v2/business/:business/balance` | `BusinessResource::balance()` / `wallet()` | supported | `wallet()` alias است. |
| Transactions | `GET /v3/business/:business/transaction` | `BusinessResource::transactions()` | supported | queryها pass-through هستند. |
| IPG | `POST https://ipg.vandar.io/api/v4/send` | `IpgResource::send()` | supported | بدون bearer authorization؛ API key در payload ارسال می‌شود. |
| IPG | `GET https://ipg.vandar.io/v4/:token` | `IpgResource::redirectUrl()` / `gatewayUrl()` | supported | URL را برمی‌گرداند و request HTTP نمی‌زند. |
| IPG | `POST https://ipg.vandar.io/api/v4/transaction` | `IpgResource::transaction()` | supported | بدون bearer authorization. |
| IPG | `POST https://ipg.vandar.io/api/v4/verify` | `IpgResource::verify()` / `verifyCallback()` | supported | verify انجام می‌دهد اما invoice را paid نمی‌کند. |
| Refunds | `POST /v3/business/:business/transaction/:transaction_id/refund` | `RefundResource::create()` / `transaction()` | supported | request مالی است و خودکار retry نمی‌شود. |
| Settlements | `POST /v3/business/:business/settlement/store` | `SettlementResource::create()` / `store()` | supported | request مالی است و خودکار retry نمی‌شود. |
| Settlements | `GET /v4/business/:business/settlement/:track_id` | `SettlementResource::find()` / `show()` | supported |  |
| Settlements | `GET /v4/business/:business/settlement` | `SettlementResource::list()` / `all()` | supported | queryها pass-through هستند. |
| Settlements | `DELETE /v4/business/:business/settlement/:track_id` | `SettlementResource::cancel()` | supported | unsafe request است و خودکار retry نمی‌شود. |
| Batch settlements | `POST https://batch.vandar.io/api/v2/business/:business/batches-settlement` | `BatchSettlementResource::create()` / `store()` | supported | از base URL مربوط به batch استفاده می‌کند. |
| Queued settlements | `POST /v3/business/:business/settlement/queued` | `QueuedSettlementResource::create()` / `store()` | supported | request مالی است و خودکار retry نمی‌شود. |
| Customers | `GET /v2/business/:business/customers` | `CustomerResource::list()` / `all()` | supported |  |
| Customers | `POST /v2/business/:business/customers` | `CustomerResource::create()` / `createIndividual()` / `createLegal()` | supported | helperها type را در صورت نبودن اضافه می‌کنند. |
| Customers | `PUT /v2/business/:business/customers/:customer` | `CustomerResource::update()` / `updateIndividual()` / `updateLegal()` | supported |  |
| Customers | `DELETE /v2/business/:business/customers/:customer` | `CustomerResource::delete()` | supported |  |
| Customer fields | `GET /v2/business/:business/customers/fields` | `CustomerFieldResource::list()` | supported | پکیج segment business را نگه می‌دارد. |
| Customer wallet | `GET /v2/business/:business/customers/:customer/wallet` | `CustomerResource::walletBalance()` | supported |  |
| Customer wallet | `POST /v2/business/:business/customers/:customer/wallet/deposit` | `CustomerResource::walletDeposit()` | supported | ledger و wallet داخلی با اپلیکیشن است. |
| Customer wallet | `POST /v2/business/:business/customers/:customer/wallet/withdraw` | `CustomerResource::walletWithdraw()` | supported | بدون idempotency اپلیکیشن retry نکنید. |
| Customer authentication | `POST /v3/business/:business/customers/:customer/authentication/kyc` | `CustomerResource::authenticationKyc()` | supported | ممکن است نیاز به فعال‌سازی داشته باشد. |
| Customer authentication | `POST /v3/business/:business/customers/:customer/authentication/shahkar` | `CustomerResource::authenticationShahkar()` | supported | با inquiry عمومی متفاوت است. |
| Customer cash-in code | `GET /v3/business/:business/customers/:customer/cash-in-code` | `CustomerResource::cashInCode()` | supported | customer-scoped است. |
| Customer cash-in code | `DELETE /v3/business/:business/customers/:customer/cash-in-code/destroy` | `CustomerResource::deleteCashInCode()` | supported | unsafe request است و خودکار retry نمی‌شود. |
| Customer cards | `POST /v3/business/:business/customers/:customer/cards` | `CardResource::create()` | supported |  |
| Customer cards | `GET /v3/business/:business/customers/:customer/cards` | `CardResource::list()` | supported |  |
| Customer cards | `DELETE /v3/business/:business/customers/:customer/cards/:card` | `CardResource::delete()` | supported |  |
| Customer cards | `POST /v3/business/:business/customers/:customer/cards/:card/inquiry` | `CardResource::inquiry()` | supported |  |
| Customer cards | `POST /v3/business/:business/customers/:customer/cards/:card/set-default` | `CardResource::setDefault()` | supported |  |
| Customer cards | `POST /v3/business/:business/customers/:customer/cards/to-iban` | `CardResource::toIban()` | supported |  |
| Customer IBANs | `POST /v3/business/:business/customers/:customer/ibans` | `IbanResource::create()` | supported |  |
| Customer IBANs | `GET /v3/business/:business/customers/:customer/ibans` | `IbanResource::list()` / `all()` | supported |  |
| Customer IBANs | `DELETE /v3/business/:business/customers/:customer/ibans` | `IbanResource::delete()` با `VANDAR_IBAN_DELETE_ENDPOINT_STYLE=documented` | docs ambiguity | IBAN در body درخواست DELETE ارسال می‌شود. قبل از production با API واقعی Vandar تست کنید. |
| Customer IBANs | `DELETE /v3/business/:business/customers/:customer/ibans/:iban` | `IbanResource::delete()` با پیش‌فرض `VANDAR_IBAN_DELETE_ENDPOINT_STYLE=path` | docs ambiguity | رفتار پیش‌فرض برای سازگاری حفظ شده است. |
| Customer IBANs | `POST /v3/business/:business/customers/:customer/ibans/:iban/inquiry` | `IbanResource::inquiry()` | supported |  |
| Customer IBANs | `POST /v3/business/:business/customers/:customer/ibans/:iban/set-default` | `IbanResource::setDefault()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/kyc` | `InquiryResource::kyc()` | supported | response همان چیزی است که Vandar برمی‌گرداند. |
| Inquiries | `POST /v3/business/:business/customers/inquiry/shahkar` | `InquiryResource::shahkar()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/nid` | `InquiryResource::nationalId()` / `nid()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/iban` | `InquiryResource::iban()` | supported |  |
| Inquiries | `POST /v3/business/:business/customers/inquiry/card` | `InquiryResource::card()` | supported |  |
| Avand / account | `GET /v3/business/:business/settlement/account/:iban/last-balance` | `AvandResource::lastBalance()` | supported |  |
| Avand / account | `GET /v3/business/:business/settlement/account/statement` | `AvandResource::statement()` | supported |  |
| Avand / account | `GET /v3/business/:business/settlement/account/realtime-statement` | `AvandResource::realtimeStatement()` | supported |  |
| Cash-in | `GET /v3/business/:business/cash-in/code` | `AvandResource::code()` / `cashInCode()` | supported |  |
| Cash-in account | `POST /v3/business/:business/cash-in/account/deposit` | `AvandResource::deposit()` | supported | request مالی است و خودکار retry نمی‌شود. |
| Subscription / direct debit | `GET /v3/business/:business/subscription/banks/actives` | `SubscriptionResource::activeBanks()` / `banks()` | supported | ممکن است فعال‌سازی لازم باشد. |
| Subscription / direct debit | `POST /v3/business/:business/subscription/authorization/store` | `SubscriptionResource::createAuthorization()` | supported | side-effect request است. |
| Subscription / direct debit | `GET https://subscription.vandar.io/authorizations/:token` | `SubscriptionResource::authorizationUrl()` | supported | فقط URL می‌سازد. |
| Subscription / direct debit | `PATCH /v3/business/:business/subscription/authorization/:authorization_id/verify` | `SubscriptionResource::verifyAuthorization()` | supported |  |
| Subscription / direct debit | `DELETE /v3/business/:business/subscription/authorization/:authorization_id` | `SubscriptionResource::deleteAuthorization()` / `cancelAuthorization()` | supported | side-effect request است. |
| Subscription / direct debit | `POST /v3/business/:business/subscription/withdrawal/store` | `SubscriptionResource::createWithdrawal()` | supported | request مالی است و خودکار retry نمی‌شود. |
| Subscription / direct debit | `GET /v3/business/:business/subscription/withdrawal/:withdrawal_id` | `SubscriptionResource::findWithdrawal()` | supported |  |
| Subscription / direct debit | `POST /v3/business/:business/subscription/refunds` | `SubscriptionResource::createRefund()` | supported | side-effect request است و خودکار retry نمی‌شود. |
| Ravand | Official Ravand endpoint group | none | future module | Ravand فعلا پیاده‌سازی نشده و به‌عنوان قابلیت موجود معرفی نمی‌شود. |
