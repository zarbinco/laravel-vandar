# راهنمای استفاده

[فارسی](usage.md) | [English](../en/usage.md)

این پکیج غیررسمی است و وابستگی رسمی به Vandar ندارد. مثال‌ها از مقدارهای fake استفاده می‌کنند و نباید با credential واقعی جایگزین شوند مگر داخل اپلیکیشن خودتان.

`zarbinco/laravel-vandar` برای این ساخته شده که اپلیکیشن Laravel بتواند با APIهای Vandar صحبت کند. پکیج request می‌سازد، response برمی‌گرداند، tokenها را مدیریت می‌کند، package logها را redact می‌کند و fakeهای تستی می‌دهد. اما workflow پرداخت، ثبت invoice/order، کیف پول، ledger، callback handler، reconciliation و سیاست لاگ همچنان متعلق به اپلیکیشن شماست.

## نصب

```bash
composer require zarbinco/laravel-vandar
php artisan vendor:publish --tag=vandar-config
```

## تنظیمات مهم

```env
VANDAR_BUSINESS=
VANDAR_ACCESS_TOKEN=
VANDAR_REFRESH_TOKEN=
VANDAR_ACCESS_TOKEN_EXPIRES_AT=
VANDAR_TOKEN_STORE=cache
VANDAR_AUTO_REFRESH=false
VANDAR_PERSIST_CONFIG_FALLBACK_TO_CACHE=false
VANDAR_IBAN_DELETE_ENDPOINT_STYLE=path
VANDAR_API_URL=https://api.vandar.io
VANDAR_IPG_URL=https://ipg.vandar.io
VANDAR_BATCH_URL=https://batch.vandar.io
VANDAR_SUBSCRIPTION_URL=https://subscription.vandar.io
VANDAR_IPG_API_KEY=
VANDAR_IPG_CALLBACK_URL=https://example.com/payments/callback
VANDAR_HTTP_VERIFY_SSL=true
VANDAR_LOGGING_ENABLED=false
VANDAR_RETRY_MONEY_MOVING_REQUESTS=false
```

token storeهای پشتیبانی‌شده `config`، `cache` و `custom` هستند. برای production، مخصوصا وقتی چند سرور دارید، بهتر است `VANDAR_TOKEN_STORE=cache` را روی cache مشترک مثل Redis قرار دهید تا tokenهای refresh شده و lockها بین همه‌ی workerها مشترک باشند.

`VANDAR_AUTO_REFRESH` به‌صورت پیش‌فرض `false` است. اگر آن را `true` کنید، پکیج می‌تواند قبل از ارسال request احراز هویت‌شده، token نزدیک به انقضا را refresh کند. این رفتار نیاز به refresh token معتبر و token store قابل نوشتن دارد. این گزینه به معنی retry کردن همه‌ی requestهای ناموفق نیست و retry خودکار برای عملیات مالی اضافه نمی‌کند.

برای production معمولا یکی از این دو مسیر مناسب است:

- cache token store همراه با اجرای زمان‌بندی‌شده‌ی `php artisan vandar:refresh-token`
- cache token store همراه با `VANDAR_AUTO_REFRESH=true`، فقط بعد از تست کامل در اپلیکیشن خودتان

`VANDAR_ACCESS_TOKEN_EXPIRES_AT` می‌تواند زمان واقعی انقضای access token را با ISO 8601 یا Unix timestamp مشخص کند. اگر مقدار نداشته باشد یا نامعتبر باشد، رفتار قبلی بر اساس `VANDAR_ACCESS_TOKEN_TTL_SECONDS` حفظ می‌شود.

`VANDAR_PERSIST_CONFIG_FALLBACK_TO_CACHE=false` به‌صورت پیش‌فرض خاموش است. اگر با cache token store آن را `true` کنید، tokenهای fallback از config در اولین خواندن داخل cache نوشته می‌شوند و خواندن‌های بعدی از cache استفاده می‌کنند.

## استفاده‌ی پایه

```php
use Zarbinco\LaravelVandar\Facades\Vandar;

$balance = Vandar::business()->balance();

if ($balance->successful()) {
    $data = $balance->data();
}
```

همه‌ی resourceها `Zarbinco\LaravelVandar\DTO\VandarResponse` برمی‌گردانند:

```php
$response->status();
$response->json();
$response->data();
$response->message();
$response->errors();
$response->body();
$response->redactedBody();
$response->jsonParseFailed();
$response->successful();
$response->throw();
```

برای diagnostics مربوط به body خام، از `redactedBody()` استفاده کنید. در production، مقدارهای `$response->body()`، `$response->json()` یا `$response->toArray()` را مستقیم لاگ نکنید مگر اینکه JSON و headerها را جداگانه redact کرده باشید. `toArray()` برای سازگاری، JSON و headerها را حفظ می‌کند.

## Resourceها

```text
Vandar::business()
Vandar::customers()
Vandar::cards()
Vandar::ibans()
Vandar::inquiries()
Vandar::ipg()
Vandar::refunds()
Vandar::settlements()
Vandar::queuedSettlements()
Vandar::batchSettlements()
Vandar::avand()
Vandar::cashIn()
Vandar::subscriptions()
Vandar::subscription()
Vandar::directDebit()
```

## Customers

```php
$customer = Vandar::customers()->createIndividual([
    'first_name' => 'Fake',
    'last_name' => 'User',
    'mobile' => 'fake-mobile',
    'individual_national_code' => 'fake-national-code',
]);

$found = Vandar::customers()->find('fake-customer-id');
```

endpointهای authentication و cash-in-code برای customer ممکن است نیاز به فعال‌سازی از سمت Vandar داشته باشند. پکیج فقط request را می‌فرستد و response را برمی‌گرداند؛ اعتماد کردن، ذخیره کردن یا استفاده‌ی عملی از نتیجه با اپلیکیشن شماست.

## Card و IBAN

```php
$card = Vandar::cards()->create('fake-customer-id', [
    'card' => 'fake-card',
    'track_id' => 'fake-track-id',
]);

$iban = Vandar::ibans()->create('fake-customer-id', [
    'iban' => 'fake-iban',
    'track_id' => 'fake-track-id',
]);
```

`VANDAR_IBAN_DELETE_ENDPOINT_STYLE=path` پیش‌فرض است و endpoint قدیمی را نگه می‌دارد:

```text
DELETE /v3/business/{business}/customers/{customer}/ibans/{iban}
```

اگر بخواهید شکل دیگری را که در بعضی مستندات Vandar آمده امتحان کنید، می‌توانید مقدار را `documented` بگذارید:

```env
VANDAR_IBAN_DELETE_ENDPOINT_STYLE=documented
```

در این حالت endpoint زیر استفاده می‌شود و مقدار IBAN در body درخواست DELETE فرستاده می‌شود:

```text
DELETE /v3/business/{business}/customers/{customer}/ibans
```

این حالت را قبل از production حتما با API واقعی Vandar تست کنید.

## IPG

callback به‌تنهایی نشانه‌ی پرداخت نهایی نیست. حتی اگر callback وضعیت OK بدهد، اپلیکیشن باید پرداخت را verify کند و بعد مبلغ، factor/order، token و transaction id را با رکورد داخلی خودش مقایسه کند.

```php
$payment = Vandar::ipg()->send([
    'amount' => 100000,
    'callback_url' => 'https://example.com/payments/callback',
]);

$redirectUrl = Vandar::ipg()->redirectUrl('fake-payment-token');
```

الگوی امن این است که بعد از callback از `verifyCallback()` یا `verify()` استفاده کنید و تصمیم نهایی را داخل اپلیکیشن خودتان بگیرید.

## Subscription / Direct Debit

Direct Debit و Subscription ممکن است نیاز به فعال‌سازی حساب یا merchant در Vandar داشته باشند. پکیج methodها را در اختیار شما می‌گذارد، اما نگهداری authorizationها، withdrawalها، refundها، وضعیت‌ها و reconciliation با اپلیکیشن شماست.

```php
$banks = Vandar::subscriptions()->activeBanks();

$authorization = Vandar::subscriptions()->createAuthorization([
    'track_id' => 'fake-track-id',
    'mobile' => 'fake-mobile',
    'national_code' => 'fake-national-code',
]);
```

withdrawal و refund مالی به‌صورت پیش‌فرض retry خودکار نمی‌شوند. اگر timeout یا response نامشخص گرفتید، قبل از تکرار عملیات مالی حتما reconciliation انجام دهید.
