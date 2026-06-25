<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class BatchSettlementUrlRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_details_log_redacts_batch_id_path_segment(): void
    {
        Log::spy();

        Http::fake(['https://batch.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::batchSettlements()->details('fake-batch-id');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://batch.vandar.io/api/v2/business/test-business/batch-settlements/fake-batch-id');

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-batch-id')
                    && ($context['url'] ?? null) === 'https://batch.vandar.io/api/v2/business/test-business/batch-settlements/[redacted]';
            });
    }
}
