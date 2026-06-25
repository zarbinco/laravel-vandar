<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Zarbinco\LaravelVandar\DTO\TokenSet;

final class TokenSetTest extends TestCase
{
    public function test_from_array_parses_token_response(): void
    {
        $tokens = TokenSet::fromArray([
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'expires_in' => 3600,
        ]);

        $this->assertSame('Bearer', $tokens->tokenType);
        $this->assertSame('fake-access-token', $tokens->accessToken);
        $this->assertSame('fake-refresh-token', $tokens->refreshToken);
        $this->assertSame(3600, $tokens->expiresIn);
        $this->assertNotNull($tokens->expiresAt);
    }

    public function test_from_config_handles_missing_tokens_safely(): void
    {
        $this->assertNull(TokenSet::fromConfig(null, 'fake-refresh-token'));
        $this->assertNull(TokenSet::fromConfig('fake-access-token', null));
    }

    public function test_authorization_header_uses_token_type(): void
    {
        $tokens = new TokenSet('Custom', 'fake-access-token', 'fake-refresh-token');

        $this->assertSame('Custom fake-access-token', $tokens->authorizationHeader());
    }

    public function test_should_refresh_uses_expiration_window(): void
    {
        $now = CarbonImmutable::parse('2026-01-01 00:00:00');
        $tokens = new TokenSet('Bearer', 'fake-access-token', 'fake-refresh-token', 120, $now->addMinutes(30));

        $this->assertFalse($tokens->shouldRefresh($now, 60));
        $this->assertTrue($tokens->shouldRefresh($now->addMinutes(29), 60));
        $this->assertTrue($tokens->isExpired($now->addMinutes(30)));
    }

    public function test_redacted_array_hides_token_values(): void
    {
        $redacted = (new TokenSet('Bearer', 'fake-access-token', 'fake-refresh-token'))->redactedArray();

        $this->assertSame('[redacted]', $redacted['access_token']);
        $this->assertSame('[redacted]', $redacted['refresh_token']);
    }
}
