<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class InquiryLoggingRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
        config()->set('vandar.logging.enabled', true);
    }

    public function test_inquiry_logging_redacts_sensitive_payload_and_response_values(): void
    {
        Log::spy();

        Http::fake([
            'https://api.vandar.io/*' => Http::response([
                'national_code' => 'fake-national-code',
                'card' => 'fake-card',
                'iban' => 'fake-iban',
                'image' => 'fake-image',
                'mobile' => 'fake-mobile',
                'track_id' => 'fake-track-id',
            ], 200),
        ]);

        Vandar::inquiries()->kyc([
            'national_code' => 'fake-national-code',
            'card' => 'fake-card',
            'iban' => 'fake-iban',
            'image' => 'fake-image',
            'mobile' => 'fake-mobile',
            'track_id' => 'fake-track-id',
        ]);

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $encoded = json_encode($context);

                return $message === 'Vandar HTTP request'
                    && is_string($encoded)
                    && ! str_contains($encoded, 'fake-national-code')
                    && ! str_contains($encoded, 'fake-card')
                    && ! str_contains($encoded, 'fake-iban')
                    && ! str_contains($encoded, 'fake-image')
                    && ! str_contains($encoded, 'fake-mobile')
                    && str_contains($encoded, '[redacted]')
                    && str_contains($encoded, 'fake-track-id')
                    && str_contains($encoded, 'customers')
                    && str_contains($encoded, 'inquiry')
                    && str_contains($encoded, 'kyc');
            });
    }
}
