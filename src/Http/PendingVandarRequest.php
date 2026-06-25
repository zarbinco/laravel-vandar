<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Http;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Throwable;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;
use Zarbinco\LaravelVandar\Support\SensitiveDataRedactor;
use Zarbinco\LaravelVandar\Support\SensitiveUrlSanitizer;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class PendingVandarRequest
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly ConfigRepository $config,
        private readonly Container $app,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function send(string $method, string $base, string $path, array $options = [], bool $auth = true, bool $allowRetry = true): VandarResponse
    {
        $method = strtoupper($method);
        $url = $this->joinUrl($this->baseUrl($base), $path);
        $sensitivePathSegments = $this->sensitivePathSegmentsFromOptions($options);
        $extraSensitiveKeys = $this->extraSensitiveKeysFromOptions($options);
        $options = $this->withoutInternalOptions($options);
        $safeUrl = SensitiveUrlSanitizer::sanitize($url, sensitivePathSegments: $sensitivePathSegments);
        $request = $this->http
            ->acceptJson()
            ->asJson()
            ->timeout($this->intConfig('vandar.http.timeout', 20))
            ->connectTimeout($this->intConfig('vandar.http.connect_timeout', 10))
            ->withOptions(['verify' => (bool) $this->config->get('vandar.http.verify_ssl', true)]);

        if ($allowRetry && $method === 'GET' && (bool) $this->config->get('vandar.http.retry.enabled', false)) {
            $request = $request->retry(
                $this->intConfig('vandar.http.retry.times', 1),
                $this->intConfig('vandar.http.retry.sleep_ms', 500),
            );
        }

        if ($auth) {
            $authorizationHeader = $this->app->make(TokenManager::class)->authorizationHeader();

            if ($authorizationHeader !== null) {
                $request = $request->withHeaders([
                    'Authorization' => $authorizationHeader,
                ]);
            }
        }

        try {
            $response = $request->send($method, $url, $options);
        } catch (ConnectionException $exception) {
            throw new VandarRequestException(
                message: 'Unable to connect to Vandar.',
                response: [
                    'method' => $method,
                    'url' => $safeUrl,
                    'payload' => SensitiveDataRedactor::redact($this->payloadFromOptions($options), $extraSensitiveKeys),
                ],
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new VandarRequestException(
                message: 'Vandar HTTP request failed.',
                response: [
                    'method' => $method,
                    'url' => $safeUrl,
                    'payload' => SensitiveDataRedactor::redact($this->payloadFromOptions($options), $extraSensitiveKeys),
                ],
                previous: $exception,
            );
        }

        $vandarResponse = $this->toVandarResponse($response);
        $this->logSummary($method, $safeUrl, $options, $vandarResponse, $extraSensitiveKeys);

        return $vandarResponse;
    }

    private function baseUrl(string $base): string
    {
        $baseUrl = $this->config->get("vandar.base_urls.{$base}");

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new VandarRequestException("Base URL [{$base}] is not configured.");
        }

        return $baseUrl;
    }

    private function joinUrl(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    private function sensitivePathSegmentsFromOptions(array $options): array
    {
        $segments = $options['_sensitive_path_segments'] ?? [];

        if (! is_array($segments)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $segment): string => (string) $segment, $segments),
            static fn (string $segment): bool => $segment !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function withoutInternalOptions(array $options): array
    {
        unset($options['_sensitive_path_segments']);
        unset($options['_extra_sensitive_keys']);

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    private function extraSensitiveKeysFromOptions(array $options): array
    {
        $keys = $options['_extra_sensitive_keys'] ?? [];

        if (! is_array($keys)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $key): string => (string) $key, $keys),
            static fn (string $key): bool => $key !== '',
        ));
    }

    private function toVandarResponse(Response $response): VandarResponse
    {
        $json = $response->json();

        return new VandarResponse(
            status: $response->status(),
            json: is_array($json) ? $json : [],
            headers: $response->headers(),
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function payloadFromOptions(array $options): array
    {
        foreach (['json', 'form_params', 'body', 'query'] as $key) {
            if (isset($options[$key]) && is_array($options[$key])) {
                return $options[$key];
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function logSummary(string $method, string $url, array $options, VandarResponse $response, array $extraSensitiveKeys): void
    {
        if (! (bool) $this->config->get('vandar.logging.enabled', false)) {
            return;
        }

        Log::debug('Vandar HTTP request', SensitiveDataRedactor::redact([
            'method' => $method,
            'url' => $url,
            'status' => $response->status(),
            'payload' => $this->payloadFromOptions($options),
            'response' => $response->json(),
            'headers' => $response->headers(),
        ], $extraSensitiveKeys));
    }

    private function intConfig(string $key, int $default): int
    {
        $value = $this->config->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }
}
