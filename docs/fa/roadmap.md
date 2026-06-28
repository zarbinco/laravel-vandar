# Roadmap

[فارسی](roadmap.md) | [English](../en/roadmap.md)

## همین حالا موجود است

- HTTP client
- token refresh
- Business APIs
- Customers
- Cards
- IBANs
- Inquiries
- IPG
- Refunds
- Settlements
- Queued settlements
- Batch settlements
- Avand/Cash-in
- Subscription / Direct Debit
- Testing fakes

## برنامه‌های احتمالی

- Ravand، card issuing و banking APIs
- token store دیتابیس رمزنگاری‌شده به‌صورت اختیاری
- response DTOهای typed بیشتر، هرجا واقعا مفید باشد

## خارج از محدوده‌ی پیش‌فرض

- modelهای اختصاصی اپلیکیشن
- payment table
- route یا controller
- UI component
- workflowهای کسب‌وکار
- حسابداری، سند یا voucher logic

پکیج باید SDK-first و app-agnostic بماند. model، migration، route، controller، workflow، payment record، wallet update، reconciliation، logging policy و persistence باید در اپلیکیشن مصرف‌کننده ساخته شوند.
