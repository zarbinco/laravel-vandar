<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class QueuedSettlementResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_create_calls_queued_settlement_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        $response = Vandar::queuedSettlements()->create($this->payload());

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/queued'
            && $request['iban'] === 'fake-iban');
    }

    public function test_store_alias_calls_create_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::queuedSettlements()->store($this->payload());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/queued');
    }

    public function test_list_calls_queued_settlement_list_endpoint_with_query(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::queuedSettlements()->list(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/settlement/queued')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_all_alias_calls_list_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::queuedSettlements()->all(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/settlement/queued'));
    }

    public function test_find_calls_queued_settlement_detail_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::queuedSettlements()->find('fake-queued-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/queued/fake-queued-id');
    }

    public function test_show_alias_calls_find_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::queuedSettlements()->show('fake-queued-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/queued/fake-queued-id');
    }

    public function test_cancel_calls_queued_settlement_cancel_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::queuedSettlements()->cancel(['id' => 'fake-queued-id']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/queued/cancel'
            && $request['id'] === 'fake-queued-id');
    }

    public function test_cancel_by_id_calls_cancel_with_id_payload(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::queuedSettlements()->cancelById('fake-queued-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/queued/cancel'
            && $request['id'] === 'fake-queued-id');
    }

    public function test_explicit_business_argument_overrides_config(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::queuedSettlements()->list(business: 'explicit-business');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/explicit-business/settlement/queued');
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        Vandar::queuedSettlements()->list();
    }

    public function test_authorization_header_is_attached_when_access_token_exists(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::queuedSettlements()->create($this->payload());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_failed_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        $response = Vandar::queuedSettlements()->create($this->payload());

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(500, $response->status());
    }

    public function test_create_is_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::queuedSettlements()->create($this->payload());

        Http::assertSentCount(1);
    }

    public function test_cancel_is_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::queuedSettlements()->cancel(['id' => 'fake-queued-id']);

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
