<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class OfficialEndpointContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.ipg.api_key', 'fake-ipg-api-key');
        config()->set('vandar.ipg.callback_url', 'https://example.com/fake-callback');
        config()->set('vandar.base_urls.subscription', 'https://subscription.vandar.io');
    }

    /**
     * @param  Closure(): void  $call
     * @param  Closure(Request): bool|null  $payload
     */
    #[DataProvider('implementedEndpointContracts')]
    public function test_implemented_resource_methods_call_expected_official_endpoint_contracts(
        Closure $call,
        string $method,
        string $url,
        bool $expectsAuthorization,
        ?Closure $payload = null,
    ): void {
        Http::fake([
            'https://api.vandar.io/*' => Http::response(['ok' => true], 200),
            'https://ipg.vandar.io/*' => Http::response(['ok' => true], 200),
            'https://batch.vandar.io/*' => Http::response(['ok' => true], 200),
        ]);

        $call();

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($method, $url, $expectsAuthorization, $payload): bool {
            $authorizationMatches = $expectsAuthorization
                ? $request->hasHeader('Authorization', 'Bearer fake-access-token')
                : ! $request->hasHeader('Authorization');

            return $request->method() === $method
                && $request->url() === $url
                && $authorizationMatches
                && ($payload === null || $payload($request));
        });
    }

    public function test_ipg_redirect_url_matches_official_gateway_contract(): void
    {
        Http::fake();

        $this->assertSame(
            'https://ipg.vandar.io/v4/fake-payment-token',
            Vandar::ipg()->redirectUrl('fake-payment-token'),
        );

        Http::assertNothingSent();
    }

    public function test_subscription_authorization_url_matches_official_redirect_contract(): void
    {
        Http::fake();

        $this->assertSame(
            'https://subscription.vandar.io/authorizations/fake-authorization-token',
            Vandar::subscriptions()->authorizationUrl('fake-authorization-token'),
        );

        Http::assertNothingSent();
    }

    public function test_endpoint_support_documentation_exists_and_readme_links_it(): void
    {
        $root = dirname(__DIR__, 2);
        $readme = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'README.md');
        $matrix = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'endpoint-support.md');

        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'endpoint-support.md');
        $this->assertStringContainsString('docs/endpoint-support.md', $readme);
        $this->assertStringContainsString('Customer cards', $matrix);
        $this->assertStringContainsString('Customer card endpoints are documented by Vandar and covered by package contract tests.', $matrix);
        $this->assertStringContainsString('CustomerResource::authenticationKyc()', $matrix);
        $this->assertStringContainsString('CustomerResource::authenticationShahkar()', $matrix);
        $this->assertStringContainsString('CustomerResource::cashInCode()', $matrix);
        $this->assertStringContainsString('CustomerResource::deleteCashInCode()', $matrix);
        $this->assertStringContainsString('SubscriptionResource::createAuthorization()', $matrix);
        $this->assertStringContainsString('SubscriptionResource::createWithdrawal()', $matrix);
        $this->assertStringContainsString('SubscriptionResource::createRefund()', $matrix);
        $this->assertStringContainsString('Subscription / direct debit', $matrix);
        $this->assertStringContainsString('| Ravand | Official Ravand endpoint group | none | future module |', $matrix);
        $this->assertStringNotContainsString('official facts used here did not enumerate each customer-card endpoint', $matrix);

        preg_match('/## Features(?P<features>.*?)## Installation/s', $readme, $matches);
        $features = (string) ($matches['features'] ?? '');

        $this->assertStringContainsString('Subscription / Direct Debit APIs', $features);
        $this->assertStringNotContainsString('Ravand', $features);
    }

    /**
     * @param  Closure(): mixed  $call
     */
    #[DataProvider('subscriptionSideEffectContracts')]
    public function test_subscription_side_effect_contracts_are_not_retried_by_default(Closure $call): void
    {
        config()->set('vandar.rate_limit.max_retry_after_seconds', 0);

        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['message' => 'Too many requests'], 429, ['Retry-After' => '0'])
                ->push(['ok' => true], 200),
        ]);

        $response = $call();

        $this->assertTrue($response->tooManyRequests());
        Http::assertSentCount(1);
    }

    /**
     * @return array<string, array{Closure(): void, string, string, bool, 4?: Closure(Request): bool}>
     */
    public static function implementedEndpointContracts(): array
    {
        return [
            'business info' => [
                static fn (): mixed => Vandar::business()->info(),
                'GET',
                'https://api.vandar.io/v2/business/test-business',
                true,
            ],
            'business iam' => [
                static fn (): mixed => Vandar::business()->users(),
                'GET',
                'https://api.vandar.io/v2/business/test-business/iam',
                true,
            ],
            'business balance' => [
                static fn (): mixed => Vandar::business()->balance(),
                'GET',
                'https://api.vandar.io/v2/business/test-business/balance',
                true,
            ],
            'business transactions' => [
                static fn (): mixed => Vandar::business()->transactions(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/transaction',
                true,
            ],
            'ipg send' => [
                static fn (): mixed => Vandar::ipg()->send(['amount' => 100000]),
                'POST',
                'https://ipg.vandar.io/api/v4/send',
                false,
                static fn (Request $request): bool => $request['amount'] === 100000
                    && $request['api_key'] === 'fake-ipg-api-key'
                    && $request['callback_url'] === 'https://example.com/fake-callback',
            ],
            'ipg transaction' => [
                static fn (): mixed => Vandar::ipg()->transaction('fake-payment-token'),
                'POST',
                'https://ipg.vandar.io/api/v4/transaction',
                false,
                static fn (Request $request): bool => $request['token'] === 'fake-payment-token'
                    && $request['api_key'] === 'fake-ipg-api-key',
            ],
            'ipg verify' => [
                static fn (): mixed => Vandar::ipg()->verify('fake-payment-token'),
                'POST',
                'https://ipg.vandar.io/api/v4/verify',
                false,
                static fn (Request $request): bool => $request['token'] === 'fake-payment-token'
                    && $request['api_key'] === 'fake-ipg-api-key',
            ],
            'refund create' => [
                static fn (): mixed => Vandar::refunds()->create('fake-transaction-id', ['amount' => 100000]),
                'POST',
                'https://api.vandar.io/v3/business/test-business/transaction/fake-transaction-id/refund',
                true,
                static fn (Request $request): bool => $request['amount'] === 100000,
            ],
            'settlement create' => [
                static fn (): mixed => Vandar::settlements()->create(['iban' => 'fake-iban', 'amount' => 100000]),
                'POST',
                'https://api.vandar.io/v3/business/test-business/settlement/store',
                true,
                static fn (Request $request): bool => $request['iban'] === 'fake-iban'
                    && $request['amount'] === 100000,
            ],
            'settlement find' => [
                static fn (): mixed => Vandar::settlements()->find('fake-track-id'),
                'GET',
                'https://api.vandar.io/v4/business/test-business/settlement/fake-track-id',
                true,
            ],
            'settlement list' => [
                static fn (): mixed => Vandar::settlements()->list(),
                'GET',
                'https://api.vandar.io/v4/business/test-business/settlement',
                true,
            ],
            'settlement cancel' => [
                static fn (): mixed => Vandar::settlements()->cancel('fake-track-id'),
                'DELETE',
                'https://api.vandar.io/v4/business/test-business/settlement/fake-track-id',
                true,
            ],
            'settlement banks' => [
                static fn (): mixed => Vandar::settlements()->banks(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/settlement/banks',
                true,
            ],
            'batch settlement create' => [
                static fn (): mixed => Vandar::batchSettlements()->create(['settlements' => [['amount' => 100000]]]),
                'POST',
                'https://batch.vandar.io/api/v2/business/test-business/batches-settlement',
                true,
                static fn (Request $request): bool => is_array($request['settlements'] ?? null),
            ],
            'batch settlement list' => [
                static fn (): mixed => Vandar::batchSettlements()->list(),
                'GET',
                'https://batch.vandar.io/api/v2/business/test-business/batches',
                true,
            ],
            'batch settlement details' => [
                static fn (): mixed => Vandar::batchSettlements()->details('fake-batch-id'),
                'GET',
                'https://batch.vandar.io/api/v2/business/test-business/batch-settlements/fake-batch-id',
                true,
            ],
            'queued settlement create' => [
                static fn (): mixed => Vandar::queuedSettlements()->create(['amount' => 100000]),
                'POST',
                'https://api.vandar.io/v3/business/test-business/settlement/queued',
                true,
                static fn (Request $request): bool => $request['amount'] === 100000,
            ],
            'queued settlement list' => [
                static fn (): mixed => Vandar::queuedSettlements()->list(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/settlement/queued',
                true,
            ],
            'queued settlement find' => [
                static fn (): mixed => Vandar::queuedSettlements()->find('fake-queued-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/settlement/queued/fake-queued-id',
                true,
            ],
            'queued settlement cancel' => [
                static fn (): mixed => Vandar::queuedSettlements()->cancelById('fake-queued-id'),
                'POST',
                'https://api.vandar.io/v3/business/test-business/settlement/queued/cancel',
                true,
                static fn (Request $request): bool => $request['id'] === 'fake-queued-id',
            ],
            'customers list' => [
                static fn (): mixed => Vandar::customers()->list(),
                'GET',
                'https://api.vandar.io/v2/business/test-business/customers',
                true,
            ],
            'customers create' => [
                static fn (): mixed => Vandar::customers()->create(['first_name' => 'Fake']),
                'POST',
                'https://api.vandar.io/v2/business/test-business/customers',
                true,
                static fn (Request $request): bool => $request['first_name'] === 'Fake',
            ],
            'customers update' => [
                static fn (): mixed => Vandar::customers()->update('fake-customer-id', ['first_name' => 'Updated']),
                'PUT',
                'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id',
                true,
                static fn (Request $request): bool => $request['first_name'] === 'Updated',
            ],
            'customers delete' => [
                static fn (): mixed => Vandar::customers()->delete('fake-customer-id'),
                'DELETE',
                'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id',
                true,
            ],
            'customers find' => [
                static fn (): mixed => Vandar::customers()->find('fake-customer-id'),
                'GET',
                'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id',
                true,
            ],
            'customer fields list' => [
                static fn (): mixed => Vandar::customers()->fields()->list(),
                'GET',
                'https://api.vandar.io/v2/business/test-business/customers/fields',
                true,
            ],
            'customer fields create' => [
                static fn (): mixed => Vandar::customers()->fields()->create(['name' => 'fake-field']),
                'POST',
                'https://api.vandar.io/v2/business/test-business/customers/fields',
                true,
                static fn (Request $request): bool => $request['name'] === 'fake-field',
            ],
            'customer fields update' => [
                static fn (): mixed => Vandar::customers()->fields()->update('fake-field-id', ['name' => 'fake-field']),
                'PUT',
                'https://api.vandar.io/v2/business/test-business/customers/fields/fake-field-id',
                true,
            ],
            'customer fields delete' => [
                static fn (): mixed => Vandar::customers()->fields()->delete('fake-field-id'),
                'DELETE',
                'https://api.vandar.io/v2/business/test-business/customers/fields/fake-field-id',
                true,
            ],
            'customer fields find' => [
                static fn (): mixed => Vandar::customers()->fields()->find('fake-field-id'),
                'GET',
                'https://api.vandar.io/v2/business/test-business/customers/fields/fake-field-id',
                true,
            ],
            'customer wallet balance' => [
                static fn (): mixed => Vandar::customers()->walletBalance('fake-customer-id'),
                'GET',
                'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id/wallet',
                true,
            ],
            'customer wallet deposit' => [
                static fn (): mixed => Vandar::customers()->walletDeposit('fake-customer-id', ['amount' => 100000]),
                'POST',
                'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id/wallet/deposit',
                true,
                static fn (Request $request): bool => $request['amount'] === 100000,
            ],
            'customer wallet withdraw' => [
                static fn (): mixed => Vandar::customers()->walletWithdraw('fake-customer-id', ['amount' => 50000]),
                'POST',
                'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id/wallet/withdraw',
                true,
                static fn (Request $request): bool => $request['amount'] === 50000,
            ],
            'customer transactions' => [
                static fn (): mixed => Vandar::customers()->transactions('fake-customer-id', ['page' => 1]),
                'POST',
                'https://api.vandar.io/v2/business/test-business/customers/fake-customer-id/transactions',
                true,
                static fn (Request $request): bool => $request['page'] === 1,
            ],
            'customer authentication kyc' => [
                static fn (): mixed => Vandar::customers()->authenticationKyc('fake-customer-id', [
                    'national_code' => 'fake-national-code',
                    'birth_date' => 'fake-birth-date',
                ]),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/authentication/kyc',
                true,
                static fn (Request $request): bool => $request['national_code'] === 'fake-national-code'
                    && $request['birth_date'] === 'fake-birth-date',
            ],
            'customer authentication shahkar' => [
                static fn (): mixed => Vandar::customers()->authenticationShahkar('fake-customer-id', [
                    'mobile' => 'fake-mobile',
                    'national_code' => 'fake-national-code',
                ]),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/authentication/shahkar',
                true,
                static fn (Request $request): bool => $request['mobile'] === 'fake-mobile'
                    && $request['national_code'] === 'fake-national-code',
            ],
            'customer cash in code' => [
                static fn (): mixed => Vandar::customers()->cashInCode('fake-customer-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cash-in-code',
                true,
            ],
            'customer delete cash in code' => [
                static fn (): mixed => Vandar::customers()->deleteCashInCode('fake-customer-id'),
                'DELETE',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cash-in-code/destroy',
                true,
            ],
            'cards create' => [
                static fn (): mixed => Vandar::cards()->create('fake-customer-id', ['card' => 'fake-card']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cards',
                true,
                static fn (Request $request): bool => $request['card'] === 'fake-card',
            ],
            'cards list' => [
                static fn (): mixed => Vandar::cards()->list('fake-customer-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cards',
                true,
            ],
            'cards delete' => [
                static fn (): mixed => Vandar::cards()->delete('fake-customer-id', 'fake-card-id'),
                'DELETE',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cards/fake-card-id',
                true,
            ],
            'cards inquiry' => [
                static fn (): mixed => Vandar::cards()->inquiry('fake-customer-id', 'fake-card-id', ['track_id' => 'fake-track-id']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cards/fake-card-id/inquiry',
                true,
                static fn (Request $request): bool => $request['track_id'] === 'fake-track-id',
            ],
            'cards set default' => [
                static fn (): mixed => Vandar::cards()->setDefault('fake-customer-id', 'fake-card-id'),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cards/fake-card-id/set-default',
                true,
            ],
            'cards to iban' => [
                static fn (): mixed => Vandar::cards()->toIban('fake-customer-id', ['card' => 'fake-card']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/cards/to-iban',
                true,
                static fn (Request $request): bool => $request['card'] === 'fake-card',
            ],
            'ibans create' => [
                static fn (): mixed => Vandar::ibans()->create('fake-customer-id', ['iban' => 'fake-iban']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans',
                true,
                static fn (Request $request): bool => $request['iban'] === 'fake-iban',
            ],
            'ibans list' => [
                static fn (): mixed => Vandar::ibans()->list('fake-customer-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans',
                true,
            ],
            'ibans delete specific current package contract' => [
                static fn (): mixed => Vandar::ibans()->delete('fake-customer-id', 'fake-iban-id'),
                'DELETE',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban-id',
                true,
            ],
            'ibans inquiry' => [
                static fn (): mixed => Vandar::ibans()->inquiry('fake-customer-id', 'fake-iban-id', ['track_id' => 'fake-track-id']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban-id/inquiry',
                true,
                static fn (Request $request): bool => $request['track_id'] === 'fake-track-id',
            ],
            'ibans set default' => [
                static fn (): mixed => Vandar::ibans()->setDefault('fake-customer-id', 'fake-iban-id'),
                'POST',
                'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban-id/set-default',
                true,
            ],
            'inquiry kyc' => self::inquiryCase('kyc', static fn (): mixed => Vandar::inquiries()->kyc(['track_id' => 'fake-track-id'])),
            'inquiry shahkar' => self::inquiryCase('shahkar', static fn (): mixed => Vandar::inquiries()->shahkar(['track_id' => 'fake-track-id'])),
            'inquiry nid' => self::inquiryCase('nid', static fn (): mixed => Vandar::inquiries()->nid(['track_id' => 'fake-track-id'])),
            'inquiry nid image' => self::inquiryCase('nid-image', static fn (): mixed => Vandar::inquiries()->nidImage(['track_id' => 'fake-track-id'])),
            'inquiry fida' => self::inquiryCase('fida', static fn (): mixed => Vandar::inquiries()->fida(['track_id' => 'fake-track-id'])),
            'inquiry postal code' => self::inquiryCase('postal-code', static fn (): mixed => Vandar::inquiries()->postalCode(['track_id' => 'fake-track-id'])),
            'inquiry company information' => self::inquiryCase('company-information', static fn (): mixed => Vandar::inquiries()->companyInformation(['track_id' => 'fake-track-id'])),
            'inquiry company signature' => self::inquiryCase('company-signature', static fn (): mixed => Vandar::inquiries()->companySignature(['track_id' => 'fake-track-id'])),
            'inquiry national code iban' => self::inquiryCase('national-code-iban', static fn (): mixed => Vandar::inquiries()->nationalCodeIban(['track_id' => 'fake-track-id'])),
            'inquiry match national code iban' => self::inquiryCase('match-national-code-iban', static fn (): mixed => Vandar::inquiries()->matchNationalCodeIban(['track_id' => 'fake-track-id'])),
            'inquiry match mobile card' => self::inquiryCase('match-mobile-card', static fn (): mixed => Vandar::inquiries()->matchMobileCard(['track_id' => 'fake-track-id'])),
            'inquiry iban' => self::inquiryCase('iban', static fn (): mixed => Vandar::inquiries()->iban(['track_id' => 'fake-track-id'])),
            'inquiry card' => self::inquiryCase('card', static fn (): mixed => Vandar::inquiries()->card(['track_id' => 'fake-track-id'])),
            'inquiry card to iban' => self::inquiryCase('card-to-iban', static fn (): mixed => Vandar::inquiries()->cardToIban(['track_id' => 'fake-track-id'])),
            'avand last balance' => [
                static fn (): mixed => Vandar::avand()->lastBalance('fake-iban'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/settlement/account/fake-iban/last-balance',
                true,
            ],
            'avand statement' => [
                static fn (): mixed => Vandar::avand()->statement(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/settlement/account/statement',
                true,
            ],
            'avand realtime statement' => [
                static fn (): mixed => Vandar::avand()->realtimeStatement(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/settlement/account/realtime-statement',
                true,
            ],
            'avand add label' => [
                static fn (): mixed => Vandar::avand()->addTransactionLabel('fake-iban', 'fake-tracking-code', ['label' => 'fake-label']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/settlement/account/fake-iban/transaction/fake-tracking-code/label',
                true,
                static fn (Request $request): bool => $request['label'] === 'fake-label',
            ],
            'avand remove label' => [
                static fn (): mixed => Vandar::avand()->removeTransactionLabel('fake-iban', 'fake-tracking-code'),
                'DELETE',
                'https://api.vandar.io/v3/business/test-business/settlement/account/fake-iban/transaction/fake-tracking-code/label',
                true,
            ],
            'cash in code' => [
                static fn (): mixed => Vandar::cashIn()->code(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/cash-in/code',
                true,
            ],
            'cash in pic transactions' => [
                static fn (): mixed => Vandar::cashIn()->picTransactions(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/cash-in/pic/transactions',
                true,
            ],
            'cash in suspicious payments' => [
                static fn (): mixed => Vandar::cashIn()->suspiciousPayments(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/cash-in/suspicious-payment',
                true,
            ],
            'cash in resolve suspicious payment' => [
                static fn (): mixed => Vandar::cashIn()->suspiciousPayment('fake-suspicious-id', ['status' => 'resolved']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/cash-in/suspicious-payment/fake-suspicious-id',
                true,
                static fn (Request $request): bool => $request['status'] === 'resolved',
            ],
            'cash in account' => [
                static fn (): mixed => Vandar::cashIn()->account(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/cash-in/account',
                true,
            ],
            'cash in account deposit' => [
                static fn (): mixed => Vandar::cashIn()->deposit(['amount' => 100000]),
                'POST',
                'https://api.vandar.io/v3/business/test-business/cash-in/account/deposit',
                true,
                static fn (Request $request): bool => $request['amount'] === 100000,
            ],
            'cash in account balance' => [
                static fn (): mixed => Vandar::cashIn()->balance(['track_id' => 'fake-track-id']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/cash-in/account/balance',
                true,
                static fn (Request $request): bool => $request['track_id'] === 'fake-track-id',
            ],
            'subscription active banks' => [
                static fn (): mixed => Vandar::subscriptions()->activeBanks(),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/banks/actives',
                true,
            ],
            'subscription authorization create' => [
                static fn (): mixed => Vandar::subscriptions()->createAuthorization(['track_id' => 'fake-track-id']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/store',
                true,
                static fn (Request $request): bool => $request['track_id'] === 'fake-track-id',
            ],
            'subscription authorization verify' => [
                static fn (): mixed => Vandar::subscriptions()->verifyAuthorization('fake-authorization-id', ['track_id' => 'fake-track-id']),
                'PATCH',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id/verify',
                true,
                static fn (Request $request): bool => $request['track_id'] === 'fake-track-id',
            ],
            'subscription authorization search' => [
                static fn (): mixed => Vandar::subscriptions()->searchAuthorization('fake-authorization-id', ['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id/search?page=1',
                true,
            ],
            'subscription authorization list' => [
                static fn (): mixed => Vandar::subscriptions()->listAuthorizations(['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization?page=1',
                true,
            ],
            'subscription authorization calculation' => [
                static fn (): mixed => Vandar::subscriptions()->authorizationCalculation('fake-authorization-id', ['amount' => 100000]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id/calculation?amount=100000',
                true,
            ],
            'subscription authorization delete' => [
                static fn (): mixed => Vandar::subscriptions()->deleteAuthorization('fake-authorization-id'),
                'DELETE',
                'https://api.vandar.io/v3/business/test-business/subscription/authorization/fake-authorization-id',
                true,
            ],
            'subscription withdrawal create' => [
                static fn (): mixed => Vandar::subscriptions()->createWithdrawal(['amount' => 100000]),
                'POST',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/store',
                true,
                static fn (Request $request): bool => $request['amount'] === 100000,
            ],
            'subscription withdrawal find' => [
                static fn (): mixed => Vandar::subscriptions()->findWithdrawal('fake-withdrawal-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/fake-withdrawal-id',
                true,
            ],
            'subscription withdrawal by track id' => [
                static fn (): mixed => Vandar::subscriptions()->withdrawalByTrackId('fake-track-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/track-id/fake-track-id',
                true,
            ],
            'subscription withdrawal list' => [
                static fn (): mixed => Vandar::subscriptions()->listWithdrawals(['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal?page=1',
                true,
            ],
            'subscription withdrawal list by authorization' => [
                static fn (): mixed => Vandar::subscriptions()->withdrawalsForAuthorization('fake-authorization-id', ['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal?page=1&q=fake-authorization-id',
                true,
            ],
            'subscription withdrawal update' => [
                static fn (): mixed => Vandar::subscriptions()->updateWithdrawal('fake-withdrawal-id', ['status' => 'canceled']),
                'PUT',
                'https://api.vandar.io/v3/business/test-business/subscription/withdrawal/fake-withdrawal-id',
                true,
                static fn (Request $request): bool => $request['status'] === 'canceled',
            ],
            'subscription refund create' => [
                static fn (): mixed => Vandar::subscriptions()->createRefund(['withdrawal_id' => 'fake-withdrawal-id']),
                'POST',
                'https://api.vandar.io/v3/business/test-business/subscription/refunds',
                true,
                static fn (Request $request): bool => $request['withdrawal_id'] === 'fake-withdrawal-id',
            ],
            'subscription refund find' => [
                static fn (): mixed => Vandar::subscriptions()->findRefund('fake-refund-id'),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/refunds/fake-refund-id',
                true,
            ],
            'subscription refund list' => [
                static fn (): mixed => Vandar::subscriptions()->listRefunds(['page' => 1]),
                'GET',
                'https://api.vandar.io/v3/business/test-business/subscription/refunds?page=1',
                true,
            ],
        ];
    }

    /**
     * @return array{Closure(): void, string, string, bool, Closure(Request): bool}
     */
    private static function inquiryCase(string $endpoint, Closure $call): array
    {
        return [
            $call,
            'POST',
            "https://api.vandar.io/v3/business/test-business/customers/inquiry/{$endpoint}",
            true,
            static fn (Request $request): bool => $request['track_id'] === 'fake-track-id',
        ];
    }

    /**
     * @return array<string, array{Closure(): mixed}>
     */
    public static function subscriptionSideEffectContracts(): array
    {
        return [
            'authorization create' => [static fn (): mixed => Vandar::subscriptions()->createAuthorization(['track_id' => 'fake-track-id'])],
            'authorization verify' => [static fn (): mixed => Vandar::subscriptions()->verifyAuthorization('fake-authorization-id')],
            'authorization delete' => [static fn (): mixed => Vandar::subscriptions()->deleteAuthorization('fake-authorization-id')],
            'withdrawal create' => [static fn (): mixed => Vandar::subscriptions()->createWithdrawal(['amount' => 100000])],
            'withdrawal update' => [static fn (): mixed => Vandar::subscriptions()->updateWithdrawal('fake-withdrawal-id', ['status' => 'canceled'])],
            'refund create' => [static fn (): mixed => Vandar::subscriptions()->createRefund(['withdrawal_id' => 'fake-withdrawal-id'])],
        ];
    }
}
