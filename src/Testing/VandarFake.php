<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Testing;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;

final class VandarFake
{
    /**
     * @var array<string, array{method: string, base: string, path: string}>
     */
    private const LABELS = [
        'business.balance' => ['method' => 'GET', 'base' => 'api', 'path' => '/v2/business/*/balance'],
        'customers.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v2/business/*/customers'],
        'cards.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/customers/*/cards'],
        'ibans.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/customers/*/ibans'],
        'inquiries.shahkar' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/customers/inquiry/shahkar'],
        'ipg.send' => ['method' => 'POST', 'base' => 'ipg', 'path' => '/api/v4/send'],
        'ipg.verify' => ['method' => 'POST', 'base' => 'ipg', 'path' => '/api/v4/verify'],
        'refunds.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/transaction/*/refund'],
        'settlements.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/settlement/store'],
        'queued-settlements.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/settlement/queued'],
        'batch-settlements.create' => ['method' => 'POST', 'base' => 'batch', 'path' => '/api/v2/business/*/batches-settlement'],
        'avand.balance' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/cash-in/account/balance'],
        'subscriptions.banks' => ['method' => 'GET', 'base' => 'api', 'path' => '/v3/business/*/subscription/banks/actives'],
        'subscriptions.authorization.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/subscription/authorization/store'],
        'subscriptions.authorization.verify' => ['method' => 'PATCH', 'base' => 'api', 'path' => '/v3/business/*/subscription/authorization/*/verify'],
        'subscriptions.authorization.delete' => ['method' => 'DELETE', 'base' => 'api', 'path' => '/v3/business/*/subscription/authorization/*'],
        'subscriptions.withdrawal.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/subscription/withdrawal/store'],
        'subscriptions.withdrawal.find' => ['method' => 'GET', 'base' => 'api', 'path' => '/v3/business/*/subscription/withdrawal/*'],
        'subscriptions.withdrawal.track' => ['method' => 'GET', 'base' => 'api', 'path' => '/v3/business/*/subscription/withdrawal/track-id/*'],
        'subscriptions.refunds.create' => ['method' => 'POST', 'base' => 'api', 'path' => '/v3/business/*/subscription/refunds'],
        'subscriptions.refunds.find' => ['method' => 'GET', 'base' => 'api', 'path' => '/v3/business/*/subscription/refunds/*'],
    ];

    /**
     * @param  array<string, mixed>  $responses
     */
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly array $responses = [],
    ) {}

    public function install(): self
    {
        if ($this->responses === []) {
            Http::fake();

            return $this;
        }

        $responses = [];

        foreach ($this->responses as $target => $definition) {
            $resolved = $this->resolveTarget((string) $target);
            $responses[$resolved['pattern']] = FakeVandarResponseFactory::make($definition);
        }

        Http::fake($responses);

        return $this;
    }

    public function assertSent(string $target, ?callable $callback = null): void
    {
        $resolved = $this->resolveTarget($target);

        Http::assertSent(fn (Request $request): bool => $this->requestMatches($request, $resolved, $callback));
    }

    public function assertNotSent(string $target, ?callable $callback = null): void
    {
        $resolved = $this->resolveTarget($target);

        Http::assertNotSent(fn (Request $request): bool => $this->requestMatches($request, $resolved, $callback));
    }

    public function assertSentCount(string $target, int $count): void
    {
        $resolved = $this->resolveTarget($target);
        $sent = Http::recorded(fn (Request $request): bool => $this->requestMatches($request, $resolved));

        Assert::assertCount($count, $sent, sprintf('Expected [%s] to be sent %d times.', $target, $count));
    }

    /**
     * @return array{method: string|null, pattern: string}
     */
    private function resolveTarget(string $target): array
    {
        if (array_key_exists($target, self::LABELS)) {
            $label = self::LABELS[$target];

            return [
                'method' => $label['method'],
                'pattern' => $this->absolutePattern($label['base'], $label['path']),
            ];
        }

        if (preg_match('/^(GET|POST|PUT|PATCH|DELETE)\s+(.+)$/i', $target, $matches) === 1) {
            return [
                'method' => strtoupper($matches[1]),
                'pattern' => trim($matches[2]),
            ];
        }

        if (str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
            return [
                'method' => null,
                'pattern' => $target,
            ];
        }

        throw new InvalidArgumentException(sprintf('Unknown Vandar fake target [%s].', $target));
    }

    private function absolutePattern(string $base, string $path): string
    {
        $baseUrl = $this->config->get("vandar.base_urls.{$base}");

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new InvalidArgumentException(sprintf('Base URL [%s] is not configured.', $base));
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array{method: string|null, pattern: string}  $resolved
     */
    private function requestMatches(Request $request, array $resolved, ?callable $callback = null): bool
    {
        if ($resolved['method'] !== null && $request->method() !== $resolved['method']) {
            return false;
        }

        if (! Str::is($resolved['pattern'], $request->url())) {
            return false;
        }

        return $callback === null || (bool) $callback($request);
    }
}
