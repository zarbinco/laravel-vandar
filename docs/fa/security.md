# نکات امنیتی

[فارسی](security.md) | [English](../en/security.md)

این پکیج را مثل یک transport/client ببینید. تصمیم‌های مهم مثل پرداخت نهایی، ثبت invoice، ledger، کیف پول، reconciliation، authorization، retention و سیاست لاگ داخل اپلیکیشن شما انجام می‌شود.

## credential و داده‌ی خصوصی

هیچ‌وقت credential واقعی Vandar یا داده‌ی خصوصی کاربر را داخل issue، pull request، fixture، تست، لاگ، screenshot یا فایل zip قرار ندهید.

این موارد حساس هستند:

- access token و refresh token
- IPG API key
- Authorization header
- payment token و transaction id
- card، IBAN، account number، CID و reference number
- national code، mobile، postal code، birth date، identity image و company signature
- statement، balance، settlement، cash-in، batch settlement، label و suspicious-payment identifier

refresh token حساس‌تر است، چون می‌تواند برای گرفتن access token جدید استفاده شود. دستور `vandar:refresh-token` هم tokenها را چاپ نمی‌کند.

## response خام

`VandarResponse::body()` برای debug کردن responseهای غیرمنتظره مفید است، اما ممکن است داده‌ی بانکی، پرداختی یا هویتی داشته باشد. در production آن را مستقیم لاگ نکنید.

برای diagnostics امن‌تر:

```php
logger()->warning('Unexpected Vandar response body', [
    'status' => $response->status(),
    'content_type' => $response->contentType(),
    'body' => $response->redactedBody(),
]);
```

امن‌تر:

- `$response->redactedBody()`
- context خطاهای پکیج، چون قبل از attach شدن redact می‌شود

نیازمند redaction جداگانه:

- `$response->body()`
- `$response->json()`
- `$response->headers()`
- `$response->toArray()`

`toArray()` به‌جای body خام، `redacted_body` دارد، اما JSON و headerها را برای سازگاری حفظ می‌کند. اگر قرار است آن‌ها را لاگ کنید، خودتان هم redaction انجام دهید.

## لاگ‌های پکیج

package logging به‌صورت پیش‌فرض خاموش است. وقتی فعال شود، پکیج payload، response، header، query، segmentهای حساس URL و مقدارهای standalone مثل Iranian IBAN، card/PAN، mobile و national-code-shaped value را قبل از لاگ کردن redact می‌کند.

این redaction یک کمک defensive best effort است، نه سیاست امنیتی کامل. اپلیکیشن شما همچنان باید لاگ‌ها، APM، queue payloadها، exportهای پشتیبانی، exception reporter و داده‌های ذخیره‌شده را خودش بررسی کند.

## IPG callback

callback را به‌تنهایی trusted ندانید. از `callbackHasOkStatus()` فقط برای خواندن raw callback status استفاده کنید. قبل از اینکه invoice یا order را paid کنید، از `verifyCallback()` استفاده کنید و مبلغ، factor/order، token، transaction id و رکورد داخلی خودتان را مقایسه کنید.

## داده‌های هویتی، کارت و IBAN

national code، mobile، postal code، birth date، KYC payload، card number، IBAN، account number و inquiry payloadها را خصوصی در نظر بگیرید. فقط چیزی را ذخیره کنید که واقعا لازم دارید و مقدار خام را لاگ نکنید.

## Direct Debit

payloadهای Subscription / Direct Debit می‌توانند bank، account، authorization، withdrawal، refund، track و customer identifier داشته باشند. withdrawal و refund مالی به‌صورت پیش‌فرض retry خودکار نمی‌شوند. فقط وقتی retry مالی را فعال کنید که اپلیکیشن شما idempotency و reconciliation قابل اعتماد دارد.

## SSL

`VANDAR_HTTP_VERIFY_SSL=true` مقدار پیش‌فرض است. در production آن را خاموش نکنید.

## گزارش آسیب‌پذیری

issue عمومی شامل credential، exploit detail یا داده‌ی خصوصی باز نکنید. راهنمای گزارش خصوصی در [SECURITY.md](../../SECURITY.md) آمده است.
