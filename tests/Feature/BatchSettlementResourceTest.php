<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class BatchSettlementResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_create_calls_batch_settlement_endpoint(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['ok' => true], 200)]);

        $response = Vandar::batchSettlements()->create($this->payload());

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://batch.vandar.io/api/v2/business/test-business/batches-settlement'
            && $request['settlements'][0]['iban'] === 'fake-iban');
    }

    public function test_store_alias_calls_create_endpoint(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::batchSettlements()->store($this->payload());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://batch.vandar.io/api/v2/business/test-business/batches-settlement');
    }

    public function test_list_calls_batch_list_endpoint_with_query(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::batchSettlements()->list(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://batch.vandar.io/api/v2/business/test-business/batches')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_all_alias_calls_list_endpoint(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::batchSettlements()->all(['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://batch.vandar.io/api/v2/business/test-business/batches'));
    }

    public function test_details_calls_batch_detail_endpoint(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::batchSettlements()->details('fake-batch-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://batch.vandar.io/api/v2/business/test-business/batch-settlements/fake-batch-id');
    }

    public function test_find_alias_calls_details_endpoint(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::batchSettlements()->find('fake-batch-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://batch.vandar.io/api/v2/business/test-business/batch-settlements/fake-batch-id');
    }

    public function test_show_alias_calls_details_endpoint(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::batchSettlements()->show('fake-batch-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://batch.vandar.io/api/v2/business/test-business/batch-settlements/fake-batch-id');
    }

    public function test_explicit_business_argument_overrides_config(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::batchSettlements()->list(business: 'explicit-business');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://batch.vandar.io/api/v2/business/explicit-business/batches');
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        Vandar::batchSettlements()->list();
    }

    public function test_authorization_header_is_attached_when_access_token_exists(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::batchSettlements()->create($this->payload());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_failed_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://batch.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        $response = Vandar::batchSettlements()->create($this->payload());

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(500, $response->status());
    }

    public function test_create_is_not_retried_automatically(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);

        Http::fake(['https://batch.vandar.io/*' => Http::response(['message' => 'Failed'], 500)]);

        Vandar::batchSettlements()->create($this->payload());

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'settlements' => [
                [
                    'iban' => 'fake-iban',
                    'amount' => 100000,
                    'track_id' => 'fake-track-id',
                ],
            ],
        ];
    }
}
