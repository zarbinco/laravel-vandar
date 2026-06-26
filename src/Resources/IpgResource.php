<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Zarbinco\LaravelVandar\DTO\IpgCallbackVerificationResult;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarException;
use Zarbinco\LaravelVandar\Exceptions\VandarIpgCallbackException;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\IpgApiKeyResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class IpgResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly IpgApiKeyResolver $apiKeys,
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload, ?string $apiKey = null): VandarResponse
    {
        $resolvedApiKey = $this->apiKeys->resolve($apiKey, $payload);

        if (! array_key_exists('api_key', $payload)) {
            $payload['api_key'] = $resolvedApiKey;
        }

        if (! array_key_exists('callback_url', $payload)) {
            $callbackUrl = $this->config->get('vandar.ipg.callback_url');

            if (is_string($callbackUrl) && trim($callbackUrl) !== '') {
                $payload['callback_url'] = trim($callbackUrl);
            }
        }

        return $this->client->post('ipg', '/api/v4/send', $payload, auth: false, allowRetry: false);
    }

    public function redirectUrl(string|int $token): string
    {
        return rtrim($this->baseUrl(), '/').'/v4/'.VandarPath::segment($token);
    }

    public function gatewayUrl(string|int $token): string
    {
        return $this->redirectUrl($token);
    }

    public function transaction(string|int $token, ?string $apiKey = null): VandarResponse
    {
        return $this->client->post('ipg', '/api/v4/transaction', [
            'api_key' => $this->apiKeys->resolve($apiKey),
            'token' => (string) $token,
        ], auth: false, allowRetry: false);
    }

    public function verify(string|int $token, ?string $apiKey = null): VandarResponse
    {
        return $this->client->post('ipg', '/api/v4/verify', [
            'api_key' => $this->apiKeys->resolve($apiKey),
            'token' => (string) $token,
        ], auth: false, allowRetry: false);
    }

    /**
     * @param  array<string, mixed>|Request  $source
     */
    public function verifyCallback(array|Request $source, ?string $apiKey = null): IpgCallbackVerificationResult
    {
        $token = $this->callbackToken($source);

        if ($token === null) {
            throw new VandarIpgCallbackException('Vandar IPG callback token is missing.');
        }

        return new IpgCallbackVerificationResult(
            token: $token,
            callbackStatus: $this->callbackStatus($source),
            response: $this->verify($token, $apiKey),
        );
    }

    /**
     * @param  array<string, mixed>|Request  $source
     */
    public function callbackHasOkStatus(array|Request $source): bool
    {
        return $this->callbackStatus($source) === 'OK';
    }

    /**
     * This method only checks the callback payment_status field. It does not
     * verify the payment. Applications must call verify() or verifyCallback()
     * before marking an order or invoice as paid. Use callbackHasOkStatus()
     * for raw callback status checks.
     *
     * @deprecated Use callbackHasOkStatus() for callback status checks and verifyCallback() for safe callback verification.
     *
     * @param  array<string, mixed>|Request  $source
     */
    public function callbackSucceeded(array|Request $source): bool
    {
        return $this->callbackHasOkStatus($source);
    }

    /**
     * @param  array<string, mixed>|Request  $source
     */
    public function callbackToken(array|Request $source): ?string
    {
        return $this->callbackValue($source, 'token');
    }

    /**
     * @param  array<string, mixed>|Request  $source
     */
    public function callbackStatus(array|Request $source): ?string
    {
        return $this->callbackValue($source, 'payment_status');
    }

    private function baseUrl(): string
    {
        $baseUrl = $this->config->get('vandar.base_urls.ipg');

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new VandarException('Base URL [ipg] is not configured.');
        }

        return trim($baseUrl);
    }

    /**
     * @param  array<string, mixed>|Request  $source
     */
    private function callbackValue(array|Request $source, string $key): ?string
    {
        $value = $source instanceof Request ? $source->input($key) : ($source[$key] ?? null);

        if (! is_scalar($value) || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
