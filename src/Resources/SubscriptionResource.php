<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarException;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class SubscriptionResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function activeBanks(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', VandarPath::join($this->basePath($business), 'banks/actives'), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function banks(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->activeBanks($query, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createAuthorization(array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->authorizationPath($business), 'store'), $payload, allowRetry: false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeAuthorization(array $payload, ?string $business = null): VandarResponse
    {
        return $this->createAuthorization($payload, $business);
    }

    public function authorizationUrl(string|int $token): string
    {
        return rtrim($this->subscriptionBaseUrl(), '/').VandarPath::join('authorizations', VandarPath::segment($token));
    }

    public function authorizationRedirectUrl(string|int $token): string
    {
        return $this->authorizationUrl($token);
    }

    public function mandateUrl(string|int $token): string
    {
        return $this->authorizationUrl($token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyAuthorization(string|int $authorizationId, array $payload = [], ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'PATCH',
            'api',
            VandarPath::join($this->authorizationPath($business), VandarPath::segment($authorizationId), 'verify'),
            [
                'json' => $payload,
                '_sensitive_path_segments' => [(string) $authorizationId],
            ],
            auth: true,
            allowRetry: false,
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function searchAuthorization(string|int $authorizationId, array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'api',
            VandarPath::join($this->authorizationPath($business), VandarPath::segment($authorizationId), 'search'),
            [
                'query' => $query,
                '_sensitive_path_segments' => [(string) $authorizationId],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listAuthorizations(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->authorizationPath($business), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function authorizations(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->listAuthorizations($query, $business);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function authorizationCalculation(string|int $authorizationId, array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'api',
            VandarPath::join($this->authorizationPath($business), VandarPath::segment($authorizationId), 'calculation'),
            [
                'query' => $query,
                '_sensitive_path_segments' => [(string) $authorizationId],
            ],
        );
    }

    public function deleteAuthorization(string|int $authorizationId, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'DELETE',
            'api',
            VandarPath::join($this->authorizationPath($business), VandarPath::segment($authorizationId)),
            [
                'json' => [],
                '_sensitive_path_segments' => [(string) $authorizationId],
            ],
            auth: true,
            allowRetry: false,
        );
    }

    public function cancelAuthorization(string|int $authorizationId, ?string $business = null): VandarResponse
    {
        return $this->deleteAuthorization($authorizationId, $business);
    }

    public function destroyAuthorization(string|int $authorizationId, ?string $business = null): VandarResponse
    {
        return $this->deleteAuthorization($authorizationId, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createWithdrawal(array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', VandarPath::join($this->withdrawalPath($business), 'store'), $payload, allowRetry: false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeWithdrawal(array $payload, ?string $business = null): VandarResponse
    {
        return $this->createWithdrawal($payload, $business);
    }

    public function findWithdrawal(string|int $withdrawalId, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'api',
            VandarPath::join($this->withdrawalPath($business), VandarPath::segment($withdrawalId)),
            [
                'query' => [],
                '_sensitive_path_segments' => [(string) $withdrawalId],
            ],
        );
    }

    public function showWithdrawal(string|int $withdrawalId, ?string $business = null): VandarResponse
    {
        return $this->findWithdrawal($withdrawalId, $business);
    }

    public function withdrawal(string|int $withdrawalId, ?string $business = null): VandarResponse
    {
        return $this->findWithdrawal($withdrawalId, $business);
    }

    public function withdrawalByTrackId(string|int $trackId, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'api',
            VandarPath::join($this->withdrawalPath($business), 'track-id', VandarPath::segment($trackId)),
            [
                'query' => [],
                '_sensitive_path_segments' => [(string) $trackId],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listWithdrawals(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->withdrawalPath($business), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function withdrawals(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->listWithdrawals($query, $business);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function withdrawalsForAuthorization(string|int $authorizationId, array $query = [], ?string $business = null): VandarResponse
    {
        $query['q'] = (string) $authorizationId;

        return $this->client->request(
            'GET',
            'api',
            $this->withdrawalPath($business),
            [
                'query' => $query,
                '_extra_sensitive_keys' => ['q'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateWithdrawal(string|int $withdrawalId, array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'PUT',
            'api',
            VandarPath::join($this->withdrawalPath($business), VandarPath::segment($withdrawalId)),
            [
                'json' => $payload,
                '_sensitive_path_segments' => [(string) $withdrawalId],
            ],
            auth: true,
            allowRetry: false,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createRefund(array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post('api', $this->refundsPath($business), $payload, allowRetry: false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeRefund(array $payload, ?string $business = null): VandarResponse
    {
        return $this->createRefund($payload, $business);
    }

    public function findRefund(string|int $refundId, ?string $business = null): VandarResponse
    {
        return $this->client->request(
            'GET',
            'api',
            VandarPath::join($this->refundsPath($business), VandarPath::segment($refundId)),
            [
                'query' => [],
                '_sensitive_path_segments' => [(string) $refundId],
            ],
        );
    }

    public function showRefund(string|int $refundId, ?string $business = null): VandarResponse
    {
        return $this->findRefund($refundId, $business);
    }

    public function refund(string|int $refundId, ?string $business = null): VandarResponse
    {
        return $this->findRefund($refundId, $business);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listRefunds(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->client->get('api', $this->refundsPath($business), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function refunds(array $query = [], ?string $business = null): VandarResponse
    {
        return $this->listRefunds($query, $business);
    }

    private function basePath(?string $business = null): string
    {
        return VandarPath::join('v3/business', $this->business->segment($business), 'subscription');
    }

    private function authorizationPath(?string $business = null): string
    {
        return VandarPath::join($this->basePath($business), 'authorization');
    }

    private function withdrawalPath(?string $business = null): string
    {
        return VandarPath::join($this->basePath($business), 'withdrawal');
    }

    private function refundsPath(?string $business = null): string
    {
        return VandarPath::join($this->basePath($business), 'refunds');
    }

    private function subscriptionBaseUrl(): string
    {
        $baseUrl = $this->config->get('vandar.base_urls.subscription');

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new VandarException('Base URL [subscription] is not configured.');
        }

        return trim($baseUrl);
    }
}
