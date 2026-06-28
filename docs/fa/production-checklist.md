# چک‌لیست Production

[فارسی](production-checklist.md) | [English](../en/production-checklist.md)

قبل از استفاده در production، این موارد را مرور کنید:

- پکیج را SDK/client ببینید، نه workflow کامل پرداخت.
- رکوردهای payment، invoice، wallet، order، ledger و reconciliation را داخل اپلیکیشن خودتان نگه دارید.
- `VANDAR_HTTP_VERIFY_SSL=true` را حفظ کنید.
- package logging را فقط وقتی فعال کنید که redaction و سیاست لاگ را بررسی کرده‌اید.
- برای diagnostics از `redactedBody()` استفاده کنید، نه body خام.
- `$response->body()`، `$response->json()`، full exception object یا payload بانکی خام را مستقیم لاگ نکنید.
- `$response->toArray()` را هم بدون redaction جداگانه‌ی JSON و headerها لاگ نکنید.
- callback را به‌تنهایی نشانه‌ی پرداخت نهایی ندانید.
- قبل از paid کردن هر چیز، `verifyCallback()` یا `verify()` را اجرا کنید.
- مبلغ invoice/order، factor/order id، token و transaction id را مقایسه کنید.
- duplicate callbackها را idempotent مدیریت کنید.
- wallet و ledger را idempotent به‌روزرسانی کنید.
- transaction id، authorization id، withdrawal id، refund id و track id را امن ذخیره کنید.
- برای چند سرور، cache مشترک مثل Redis را برای `VANDAR_TOKEN_STORE=cache` انتخاب کنید.
- file cache را برای token store در چند سرور استفاده نکنید.
- request مالی را بدون idempotency اپلیکیشن retry نکنید.
- timeout و response نامشخص در عملیات مالی را unknown state در نظر بگیرید و قبل از تکرار، reconciliation انجام دهید.
- national code، mobile، IBAN، card، token، authorization، withdrawal، refund، transaction، settlement و cash-in identifier را مستقیم لاگ نکنید.
- بعد از تغییر env، config cache production را به‌روز کنید.
- قبل از tag یا deploy، مطمئن شوید `composer ci` یا GitHub Actions پاس شده است.
