<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class SettlementResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_create_calls_settlement_store_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        $response = Vandar::settlements()->create($this->payload());

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/store'
            && $request['iban'] === 'fake-iban'
            && $request['amount'] === 100000
            && $request['track_id'] === 'fake-track-id');
    }

    public function test_store_alias_calls_create_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::settlements()->store($this->payload());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/store');
    }

    public function test_find_calls_settlement_status_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::settlements()->find('fake-track-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v4/business/test-business/settlement/fake-track-id');
    }

    public function test_show_alias_calls_find_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::settlements()->show('fake-track-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v4/business/test-business/settlement/fake-track-id');
    }

    public function test_list_calls_settlement_list_endpoint_with_query(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::settlements()->list(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v4/business/test-business/settlement')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_all_alias_calls_list_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::settlements()->all(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v4/business/test-business/settlement'));
    }

    public function test_cancel_calls_settlement_cancel_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::settlements()->cancel('fake-track-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v4/business/test-business/settlement/fake-track-id');
    }

    public function test_banks_calls_settlement_banks_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::settlements()->banks(['active' => true]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/settlement/banks')
            && str_contains($request->url(), 'active=1'));
    }

    public function test_explicit_business_argument_overrides_config(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::settlements()->list(business: 'explicit-business');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v4/business/explicit-business/settlement');
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        Vandar::settlements()->list();
    }

    public function test_dynamic_track_id_segment_is_encoded(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::settlements()->find('fake track/id');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v4/business/test-business/settlement/fake%20track%2Fid');
    }

    public function test_authorization_header_is_attached_when_access_token_exists(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::settlements()->create($this->payload());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_failed_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        $response = Vandar::settlements()->create($this->payload());

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(500, $response->status());
    }

    public function test_create_is_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::settlements()->create($this->payload());

        Http::assertSentCount(1);
    }

    public function test_cancel_is_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::settlements()->cancel('fake-track-id');

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'iban' => 'fake-iban',
            'amount' => 100000,
            'track_id' => 'fake-track-id',
        ];
    }
}
