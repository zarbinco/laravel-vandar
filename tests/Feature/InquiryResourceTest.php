<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Exceptions\VandarException;
use Zarbinco\LaravelVandar\Exceptions\VandarRequestException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class InquiryResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_kyc_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->kyc($payload),
            'kyc',
            [
                'first_name' => 'Fake',
                'last_name' => 'User',
                'national_code' => 'fake-national-code',
                'birthday' => 'fake-birthday',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_shahkar_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->shahkar($payload),
            'shahkar',
            [
                'mobile' => 'fake-mobile',
                'national_code' => 'fake-national-code',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_national_id_calls_nid_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->nationalId($payload),
            'nid',
            [
                'national_code' => 'fake-national-code',
                'birth_date' => 'fake-birth-date',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_nid_alias_calls_nid_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->nid($payload),
            'nid',
            [
                'national_code' => 'fake-national-code',
                'birth_date' => 'fake-birth-date',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_national_id_image_calls_nid_image_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->nationalIdImage($payload),
            'nid-image',
            [
                'national_code' => 'fake-national-code',
                'birth_date' => 'fake-birth-date',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_nid_image_alias_calls_nid_image_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->nidImage($payload),
            'nid-image',
            [
                'national_code' => 'fake-national-code',
                'birth_date' => 'fake-birth-date',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_fida_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->fida($payload),
            'fida',
            [
                'fida_code' => 'fake-fida-code',
                'birth_date' => 'fake-birth-date',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_postal_code_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->postalCode($payload),
            'postal-code',
            [
                'postal_code' => 'fake-postal-code',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_company_information_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->companyInformation($payload),
            'company-information',
            [
                'national_code' => 'fake-company-national-code',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_company_signature_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->companySignature($payload),
            'company-signature',
            [
                'national_code' => 'fake-company-national-code',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_national_code_iban_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->nationalCodeIban($payload),
            'national-code-iban',
            [
                'national_code' => 'fake-national-code',
                'iban' => 'fake-iban',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_match_national_code_iban_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->matchNationalCodeIban($payload),
            'match-national-code-iban',
            [
                'national_code' => 'fake-national-code',
                'iban' => 'fake-iban',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_match_mobile_card_calls_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->matchMobileCard($payload),
            'match-mobile-card',
            [
                'mobile' => 'fake-mobile',
                'card' => 'fake-card',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_iban_calls_standalone_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->iban($payload),
            'iban',
            [
                'iban' => 'fake-iban',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_card_calls_standalone_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->card($payload),
            'card',
            [
                'card' => 'fake-card',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_card_to_iban_calls_standalone_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->cardToIban($payload),
            'card-to-iban',
            [
                'card' => 'fake-card',
                'track_id' => 'fake-track-id',
            ],
        );
    }

    public function test_explicit_business_argument_overrides_config(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::inquiries()->kyc(['track_id' => 'fake-track-id'], business: 'explicit-business');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/explicit-business/customers/inquiry/kyc');
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        Vandar::inquiries()->kyc(['track_id' => 'fake-track-id']);
    }

    public function test_authorization_header_is_attached_when_token_exists(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::inquiries()->shahkar(['track_id' => 'fake-track-id']);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_failed_400_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Mismatch'], 400)]);

        $response = Vandar::inquiries()->matchMobileCard([
            'mobile' => 'fake-mobile',
            'card' => 'fake-card',
            'track_id' => 'fake-track-id',
        ]);

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(400, $response->status());
    }

    public function test_failed_502_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Bad gateway'], 502)]);

        $response = Vandar::inquiries()->iban([
            'iban' => 'fake-iban',
            'track_id' => 'fake-track-id',
        ]);

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(502, $response->status());
    }

    public function test_throw_on_400_maps_to_request_exception(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Mismatch'], 400)]);

        $response = Vandar::inquiries()->card([
            'card' => 'fake-card',
            'track_id' => 'fake-track-id',
        ]);

        $this->expectException(VandarRequestException::class);

        $response->throw();
    }

    public function test_response_track_id_works_for_inquiry_response(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['track_id' => 'fake-track-id'], 200)]);

        $response = Vandar::inquiries()->postalCode([
            'postal_code' => 'fake-postal-code',
            'track_id' => 'fake-track-id',
        ]);

        $this->assertSame('fake-track-id', $response->trackId());
    }

    public function test_response_data_works_for_inquiry_response(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => ['matched' => true]], 200)]);

        $response = Vandar::inquiries()->nationalCodeIban([
            'national_code' => 'fake-national-code',
            'iban' => 'fake-iban',
            'track_id' => 'fake-track-id',
        ]);

        $this->assertSame(['matched' => true], $response->data());
        $this->assertTrue($response->data('matched'));
    }

    public function test_send_calls_custom_inquiry_endpoint(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->send('custom-endpoint', $payload),
            'custom-endpoint',
            ['track_id' => 'fake-track-id'],
        );
    }

    public function test_send_trims_slashes_safely(): void
    {
        $this->assertInquiryCall(
            fn (array $payload): VandarResponse => Vandar::inquiries()->send('/custom-endpoint/', $payload),
            'custom-endpoint',
            ['track_id' => 'fake-track-id'],
        );
    }

    public function test_send_rejects_empty_endpoint(): void
    {
        $this->expectException(VandarException::class);

        Vandar::inquiries()->send('', ['track_id' => 'fake-track-id']);
    }

    public function test_send_rejects_full_url_endpoint(): void
    {
        $this->expectException(VandarException::class);

        Vandar::inquiries()->send('https://example.com', ['track_id' => 'fake-track-id']);
    }

    public function test_send_rejects_path_traversal_endpoint(): void
    {
        $this->expectException(VandarException::class);

        Vandar::inquiries()->send('../danger', ['track_id' => 'fake-track-id']);
    }

    /**
     * @param  callable(array<string, mixed>): VandarResponse  $callback
     * @param  array<string, mixed>  $payload
     */
    private function assertInquiryCall(callable $callback, string $endpoint, array $payload): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        $response = $callback($payload);

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === "https://api.vandar.io/v3/business/test-business/customers/inquiry/{$endpoint}"
            && $request['track_id'] === 'fake-track-id');
    }
}
