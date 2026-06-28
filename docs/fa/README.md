# مستندات Laravel Vandar

[فارسی](README.md) | [English](../en/README.md)

`zarbinco/laravel-vandar` یک پکیج غیررسمی Laravel برای کار با APIهای Vandar است. هدف پکیج این است که ارسال requestها، مدیریت token، دریافت response، لاگ امن‌تر، و تست‌های آفلاین را برای اپلیکیشن‌های Laravel ساده‌تر و قابل‌پیش‌بینی‌تر کند.

این پکیج رسمی Vandar نیست.

## از کجا شروع کنم؟

- [راهنمای نصب و استفاده](usage.md)
- [نکات امنیتی](security.md)
- [تست کردن با fakeها](testing.md)
- [جدول endpointهای پشتیبانی‌شده](endpoint-support.md)
- [نمونه‌ی integration پرداخت در Laravel](laravel-payment-integration.md)
- [چک‌لیست production](production-checklist.md)
- [چک‌لیست release](release-checklist.md)
- [Roadmap](roadmap.md)

## مرز مسئولیت پکیج

این پکیج فقط client/SDK است و مسئول ساخت workflow پرداخت، ثبت فاکتور، کیف پول، تایید نهایی پرداخت یا reconciliation در اپلیکیشن نیست.

اپلیکیشن شما همچنان باید callback را verify کند، مبلغ و factor/order را با داده‌ی داخلی تطبیق دهد، رکوردهای پرداخت و کیف پول را idempotent به‌روزرسانی کند، reconciliation انجام دهد، و تصمیم بگیرد چه چیزی برای لاگ کردن امن است.

## چند نکته‌ی مهم

- redaction بهتر شده، اما همچنان defensive best effort است.
- response خام و حساس API را در production مستقیم لاگ نکنید.
- `VANDAR_AUTO_REFRESH=false` مقدار پیش‌فرض است و auto-refresh فقط وقتی فعال می‌شود که خودتان روشنش کنید.
- auto-refresh هر API request ناموفق را دوباره retry نمی‌کند.
- برای production بهتر است از cache token store همراه با دستور زمان‌بندی‌شده‌ی `vandar:refresh-token` استفاده کنید، یا بعد از تست در اپلیکیشن خودتان auto-refresh را صریحا فعال کنید.
- `VANDAR_ACCESS_TOKEN_EXPIRES_AT` می‌تواند زمان واقعی انقضای access tokenهای config fallback را مشخص کند.
- `VANDAR_PERSIST_CONFIG_FALLBACK_TO_CACHE=false` یعنی persist کردن fallback tokenها در cache به‌صورت پیش‌فرض خاموش است.
- `VANDAR_IBAN_DELETE_ENDPOINT_STYLE=path` رفتار پیش‌فرض و سازگار با نسخه‌های قبلی است.
- حالت `VANDAR_IBAN_DELETE_ENDPOINT_STYLE=documented` اختیاری است و قبل از استفاده در production باید با API واقعی Vandar تست شود.
