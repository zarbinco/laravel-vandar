<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class QueuedSettlementUrlRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_find_log_redacts_queued_id_path_segment(): void
    {
        Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::queuedSettlements()->find('fake-queued-id');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/test-business/settlement/queued/fake-queued-id');

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-queued-id')
                    && ($context['url'] ?? null) === 'https://api.vandar.io/v3/business/test-business/settlement/queued/[redacted]';
            });
    }

    public function test_cancel_by_id_log_redacts_queued_id_payload_and_is_not_retried(): void
    {
        config()->set('vandar.http.retry.enabled', true);
        config()->set('vandar.http.retry.times', 3);
        Log::spy();

        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::queuedSettlements()->cancelById('fake-queued-id');

        Http::assertSentCount(1);
        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-queued-id')
                    && ($context['payload']['id'] ?? null) === '[redacted]';
            });
    }
}
