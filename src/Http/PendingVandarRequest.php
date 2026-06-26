<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Http;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;
use Zarbinco\LaravelVandar\Support\SensitiveDataRedactor;
use Zarbinco\LaravelVandar\Support\SensitiveUrlSanitizer;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class PendingVandarRequest
{
    /**
     * @var Closure(int): void|null
     */
    private static ?Closure $sleepUsing = null;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly ConfigRepository $config,
        private readonly Container $app,
    ) {}

    /**
     * @internal
     */
    public static function sleepUsing(?callable $sleeper): void
    {
        self::$sleepUsing = $sleeper === null ? null : Closure::fromCallable($sleeper);
    }

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
        $vandarResponse = $this->sendOnce($method, $url, $safeUrl, $options, $auth, $allowRetry, $extraSensitiveKeys);
        $this->logSummary($method, $safeUrl, $options, $vandarResponse, $extraSensitiveKeys);

        if ($this->shouldRetryRateLimited($method, $allowRetry, $vandarResponse)) {
            $this->sleepMilliseconds($this->rateLimitRetryDelayMilliseconds($vandarResponse));

            $vandarResponse = $this->sendOnce($method, $url, $safeUrl, $options, $auth, $allowRetry, $extraSensitiveKeys);
            $this->logSummary($method, $safeUrl, $options, $vandarResponse, $extraSensitiveKeys);
        }

        return $vandarResponse;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $extraSensitiveKeys
     */
    private function sendOnce(
        string $method,
        string $url,
        string $safeUrl,
        array $options,
        bool $auth,
        bool $allowRetry,
        array $extraSensitiveKeys,
    ): VandarResponse {
        $request = $this->http
            ->acceptJson()
            ->asJson()
            ->timeout($this->intConfig('vandar.http.timeout', 20))
            ->connectTimeout($this->intConfig('vandar.http.connect_timeout', 10))
            ->withOptions(['verify' => (bool) $this->config->get('vandar.http.verify_ssl', true)]);

        if ($allowRetry && $method === 'GET' && $this->boolConfig('vandar.http.retry.enabled', false)) {
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
            return $this->toVandarResponse($request->send($method, $url, $options));
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
        $body = $response->body();
        $json = [];
        $jsonParseFailed = false;

        if (trim($body) !== '') {
            try {
                $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

                if (is_array($decoded)) {
                    $json = $decoded;
                } elseif ($this->headersContainJsonContentType($response->headers())) {
                    $jsonParseFailed = true;
                }
            } catch (JsonException) {
                $jsonParseFailed = true;
            }
        }

        return new VandarResponse(
            status: $response->status(),
            json: $json,
            headers: $response->headers(),
            body: $body,
            jsonParseFailed: $jsonParseFailed,
        );
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    private function headersContainJsonContentType(array $headers): bool
    {
        foreach ($headers as $name => $value) {
            if (mb_strtolower((string) $name) !== 'content-type') {
                continue;
            }

            foreach ((array) $value as $headerValue) {
                if (is_scalar($headerValue) && str_contains(mb_strtolower((string) $headerValue), 'json')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
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
     * @param  array<int, string>  $extraSensitiveKeys
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

    private function boolConfig(string $key, bool $default): bool
    {
        $value = $this->config->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return is_bool($parsed) ? $parsed : $default;
        }

        if (is_numeric($value)) {
            return (bool) (int) $value;
        }

        return $default;
    }

    private function shouldRetryRateLimited(string $method, bool $allowRetry, VandarResponse $response): bool
    {
        if (! $response->rateLimited() || ! $this->boolConfig('vandar.rate_limit.aware', true)) {
            return false;
        }

        if ($this->isSafeMethod($method)) {
            return $allowRetry && $this->boolConfig('vandar.rate_limit.retry_safe_methods', true);
        }

        return $this->boolConfig('vandar.rate_limit.retry_money_moving_requests', false);
    }

    private function isSafeMethod(string $method): bool
    {
        return in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function rateLimitRetryDelayMilliseconds(VandarResponse $response): int
    {
        if (! $this->boolConfig('vandar.rate_limit.respect_retry_after', true)) {
            return 0;
        }

        $retryAfter = $response->retryAfter();

        if ($retryAfter === null) {
            return 0;
        }

        $maxRetryAfter = max(0, $this->intConfig('vandar.rate_limit.max_retry_after_seconds', 3));

        return min($retryAfter, $maxRetryAfter) * 1000;
    }

    private function sleepMilliseconds(int $milliseconds): void
    {
        if ($milliseconds <= 0) {
            return;
        }

        if (self::$sleepUsing instanceof Closure) {
            (self::$sleepUsing)($milliseconds);

            return;
        }

        usleep($milliseconds * 1000);
    }
}
