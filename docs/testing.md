# Testing

Use `Vandar::fake()` to test application code without calling real Vandar services.

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

Supported friendly labels include `business.balance`, `customers.create`, `cards.create`, `ibans.create`, `inquiries.shahkar`, `ipg.send`, `ipg.verify`, `refunds.create`, `settlements.create`, `queued-settlements.create`, `batch-settlements.create`, and `avand.balance`.

For endpoints not covered by a label, use URL-based fakes:

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

Fake responses are converted into normal SDK responses, so application tests can assert against `VandarResponse`.
