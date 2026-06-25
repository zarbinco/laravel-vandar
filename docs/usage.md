# Usage Guide

This package is unofficial and is not affiliated with Vandar. Examples use fake placeholder values only.

## Installation

The package is available on Packagist:

```bash
composer require zarbinco/laravel-vandar
```

## Business

```php
use Zarbinco\LaravelVandar\Facades\Vandar;

$balance = Vandar::business()->balance();
$transactions = Vandar::business()->transactions(['page' => 1]);
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

## Cards And IBANs

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

## Inquiries

```php
$shahkar = Vandar::inquiries()->shahkar([
    'mobile' => 'fake-mobile',
    'national_code' => 'fake-national-code',
]);

$ibanInquiry = Vandar::inquiries()->iban([
    'iban' => 'fake-iban',
    'track_id' => 'fake-track-id',
]);
```

## IPG And Refunds

```php
$payment = Vandar::ipg()->send([
    'amount' => 100000,
    'callback_url' => 'https://example.com/payments/callback',
]);

$redirectUrl = Vandar::ipg()->redirectUrl('fake-payment-token');
$verified = Vandar::ipg()->verify('fake-payment-token');

$refund = Vandar::refunds()->create('fake-transaction-id', [
    'amount' => 100000,
    'track_id' => 'fake-track-id',
]);
```

## Settlements

```php
$settlement = Vandar::settlements()->create([
    'iban' => 'fake-iban',
    'amount' => 100000,
    'track_id' => 'fake-track-id',
]);

$status = Vandar::settlements()->find('fake-track-id');
$queued = Vandar::queuedSettlements()->create([
    'iban' => 'fake-iban',
    'amount' => 100000,
]);

$batch = Vandar::batchSettlements()->create([
    'settlements' => [
        ['iban' => 'fake-iban', 'amount' => 100000],
    ],
]);
```

## Avand/Cash-In

```php
$account = Vandar::avand()->account();
$balance = Vandar::avand()->balance(['track_id' => 'fake-track-id']);
$statement = Vandar::avand()->statement(['page' => 1]);
$code = Vandar::cashIn()->code();
```

All resource calls return `Zarbinco\LaravelVandar\DTO\VandarResponse`.
