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

Supported friendly labels include `business.balance`, `customers.create`, `cards.create`, `ibans.create`, `inquiries.shahkar`, `ipg.send`, `ipg.verify`, `refunds.create`, `settlements.create`, `queued-settlements.create`, `batch-settlements.create`, `avand.balance`, `subscriptions.banks`, `subscriptions.authorization.create`, `subscriptions.authorization.verify`, `subscriptions.authorization.delete`, `subscriptions.withdrawal.create`, `subscriptions.withdrawal.find`, `subscriptions.withdrawal.track`, `subscriptions.refunds.create`, and `subscriptions.refunds.find`.

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

Fakes only replace HTTP calls. Application tests should still assert callback verification, idempotency, amount/order matching, local record updates, reconciliation behavior, and safe logging decisions.
