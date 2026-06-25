<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Resources;

use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarException;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class InquiryResource
{
    public function __construct(
        private readonly VandarClient $client,
        private readonly BusinessResolver $business,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function kyc(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('kyc', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function shahkar(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('shahkar', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function nationalId(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('nid', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function nid(array $payload, ?string $business = null): VandarResponse
    {
        return $this->nationalId($payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function nationalIdImage(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('nid-image', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function nidImage(array $payload, ?string $business = null): VandarResponse
    {
        return $this->nationalIdImage($payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fida(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('fida', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function postalCode(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('postal-code', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function companyInformation(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('company-information', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function companySignature(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('company-signature', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function nationalCodeIban(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('national-code-iban', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function matchNationalCodeIban(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('match-national-code-iban', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function matchMobileCard(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('match-mobile-card', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function iban(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('iban', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function card(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('card', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function cardToIban(array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry('card-to-iban', $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $endpoint, array $payload, ?string $business = null): VandarResponse
    {
        return $this->postInquiry($this->normalizeEndpoint($endpoint), $payload, $business);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postInquiry(string $endpoint, array $payload, ?string $business = null): VandarResponse
    {
        return $this->client->post(
            'api',
            VandarPath::join(
                'v3/business',
                VandarPath::segment($this->business->resolve($business)),
                'customers/inquiry',
                $endpoint,
            ),
            $payload,
        );
    }

    private function normalizeEndpoint(string $endpoint): string
    {
        $candidate = trim($endpoint);

        if ($candidate === '') {
            throw new VandarException('Inquiry endpoint must be a non-empty relative path.');
        }

        if (parse_url($candidate, PHP_URL_SCHEME) !== null || str_starts_with($candidate, '//')) {
            throw new VandarException('Inquiry endpoint must not be a full URL.');
        }

        $trimmed = trim($candidate, '/');

        if ($trimmed === '') {
            throw new VandarException('Inquiry endpoint must be a non-empty relative path.');
        }

        if (str_contains($trimmed, '\\')) {
            throw new VandarException('Inquiry endpoint must not contain path traversal.');
        }

        $decoded = rawurldecode($trimmed);

        if (str_contains($decoded, '..')) {
            throw new VandarException('Inquiry endpoint must not contain path traversal.');
        }

        $segments = explode('/', $trimmed);
        $encoded = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new VandarException('Inquiry endpoint must not contain path traversal.');
            }

            $encoded[] = VandarPath::segment($segment);
        }

        return implode('/', $encoded);
    }
}
