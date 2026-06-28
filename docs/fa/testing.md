# تست کردن

[فارسی](testing.md) | [English](../en/testing.md)

تست‌های پکیج به‌صورت پیش‌فرض آفلاین هستند. در تست‌ها از Laravel HTTP fake و `Vandar::fake()` استفاده می‌شود، بنابراین CI و تست محلی به credential واقعی Vandar، API key، token پرداخت، card، IBAN یا callback URL واقعی نیاز ندارد.

```php
use Zarbinco\LaravelVandar\Facades\Vandar;

Vandar::fake([
    'ipg.send' => [
        'status' => 200,
        'body' => [
            'status' => 1,
            'token' => 'fake-payment-token',
        ],
    ],
]);

$response = Vandar::ipg()->send([
    'amount' => 100000,
    'callback_url' => 'https://example.com/payments/callback',
]);

Vandar::assertSent('ipg.send');
```

labelهای آماده شامل مواردی مثل `business.balance`، `customers.create`، `cards.create`، `ibans.create`، `ipg.send`، `ipg.verify`، `refunds.create`، `settlements.create`، `avand.balance` و چند label مربوط به Subscription هستند.

اگر endpoint مورد نظر label آماده نداشت، می‌توانید fake مبتنی بر URL بسازید:

```php
Vandar::fake([
    'POST https://ipg.vandar.io/api/v4/send' => [
        'body' => ['token' => 'fake-payment-token'],
    ],
]);

Vandar::assertSent('POST https://ipg.vandar.io/api/v4/send');
Vandar::assertNotSent('ipg.verify');
Vandar::assertSentCount('ipg.send', 1);
```

fakeها فقط callهای HTTP را جایگزین می‌کنند. تست اپلیکیشن شما همچنان باید verify callback، idempotency، تطبیق مبلغ و order، به‌روزرسانی رکورد داخلی، reconciliation و تصمیم‌های logging را بررسی کند.

برای gate محلی:

```bash
composer ci
```

این دستور validation، Pint test، static analysis، PHPUnit و release audit را اجرا می‌کند.
