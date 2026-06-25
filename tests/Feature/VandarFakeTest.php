<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class VandarFakeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.ipg.api_key', 'fake-ipg-api-key');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_fake_fakes_successful_ipg_send_response(): void
    {
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
            'callback_url' => 'https://example.com/callback',
        ]);

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(200, $response->status());
        $this->assertSame('fake-payment-token', $response->json('token'));
        Vandar::assertSent('ipg.send');
    }

    public function test_fake_fakes_failed_inquiry_response(): void
    {
        Vandar::fake([
            'inquiries.shahkar' => [
                'status' => 422,
                'body' => [
                    'message' => 'Fake mismatch',
                    'errors' => ['mobile' => ['Fake validation error']],
                ],
            ],
        ]);

        $response = Vandar::inquiries()->shahkar([
            'mobile' => 'fake-mobile',
            'national_code' => 'fake-national-code',
        ]);

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(422, $response->status());
        $this->assertSame('Fake mismatch', $response->message());
        $this->assertSame(['mobile' => ['Fake validation error']], $response->errors());
        Vandar::assertSent('inquiries.shahkar');
    }

    public function test_fake_can_fake_settlement_response(): void
    {
        Vandar::fake([
            'settlements.create' => [
                'status' => 201,
                'body' => [
                    'track_id' => 'fake-track-id',
                ],
            ],
        ]);

        $response = Vandar::settlements()->create([
            'iban' => 'fake-iban',
            'amount' => 100000,
            'track_id' => 'fake-track-id',
        ]);

        $this->assertSame(201, $response->status());
        $this->assertSame('fake-track-id', $response->trackId());
        Vandar::assertSent('settlements.create');
    }

    public function test_assert_sent_accepts_callback(): void
    {
        Vandar::fake([
            'ipg.send' => ['body' => ['token' => 'fake-payment-token']],
        ]);

        Vandar::ipg()->send(['amount' => 100000]);

        Vandar::assertSent('ipg.send', fn (Request $request): bool => $request['amount'] === 100000);
    }

    public function test_assert_not_sent_and_assert_sent_count_work(): void
    {
        Vandar::fake([
            'ipg.send' => ['body' => ['token' => 'fake-payment-token']],
            'ipg.verify' => ['body' => ['status' => 1]],
        ]);

        Vandar::ipg()->send(['amount' => 100000]);
        Vandar::ipg()->send(['amount' => 200000]);

        Vandar::assertSentCount('ipg.send', 2);
        Vandar::assertNotSent('ipg.verify');
    }

    public function test_url_based_fake_and_assertion_work(): void
    {
        Vandar::fake([
            'POST https://ipg.vandar.io/api/v4/send' => [
                'body' => ['token' => 'fake-payment-token'],
            ],
        ]);

        Vandar::ipg()->send(['amount' => 100000]);

        Vandar::assertSent('POST https://ipg.vandar.io/api/v4/send');
    }

    public function test_fake_helper_does_not_expose_configured_token_values_in_response(): void
    {
        Vandar::fake([
            'business.balance' => [
                'body' => ['balance' => 100000],
            ],
        ]);

        $response = Vandar::business()->balance();
        $encoded = json_encode($response->json());

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('fake-access-token', $encoded);
        $this->assertStringNotContainsString('fake-refresh-token', $encoded);
        Vandar::assertSent('business.balance');
    }

    public function test_fake_helper_keeps_tests_offline(): void
    {
        Http::preventStrayRequests();

        Vandar::fake([
            'avand.balance' => [
                'body' => ['balance' => 100000],
            ],
        ]);

        $response = Vandar::avand()->balance(['track_id' => 'fake-track-id']);

        $this->assertSame(100000, $response->json('balance'));
        Vandar::assertSentCount('avand.balance', 1);
    }
}
