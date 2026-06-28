<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Resources\IbanResource;
use Zarbinco\LaravelVandar\Support\BusinessResolver;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class IbanResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_create_calls_customer_ibans_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 201)]);

        $response = Vandar::ibans()->create('fake-customer-id', [
            'iban' => 'fake-iban',
            'is_default' => true,
            'has_inquiry' => false,
            'track_id' => 'fake-track-id',
        ]);

        $this->assertInstanceOf(VandarResponse::class, $response);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans'
            && $request['iban'] === 'fake-iban'
            && $request['track_id'] === 'fake-track-id');
    }

    public function test_list_calls_customer_ibans_endpoint_with_query(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::ibans()->list('fake-customer-id', ['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans')
            && str_contains($request->url(), 'page=1'));
    }

    public function test_all_alias_calls_list_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::ibans()->all('fake-customer-id', ['page' => 1]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans'));
    }

    public function test_delete_calls_customer_iban_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        Vandar::ibans()->delete('fake-customer-id', 'fake-iban');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban');
    }

    public function test_delete_method_signature_remains_unchanged(): void
    {
        $method = new ReflectionMethod(IbanResource::class, 'delete');
        $parameters = $method->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertSame('customer', $parameters[0]->getName());
        $this->assertSame('iban', $parameters[1]->getName());
        $this->assertSame('business', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable());
        $this->assertNull($parameters[2]->getDefaultValue());
    }

    public function test_can_be_instantiated_with_old_two_argument_constructor(): void
    {
        $this->assertInstanceOf(IbanResource::class, $this->makeIbanResourceWithOldConstructor());
    }

    public function test_old_two_argument_constructor_uses_default_path_delete_endpoint_style(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        $this->makeIbanResourceWithOldConstructor()->delete('fake-customer-id', 'fake-iban');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban');
    }

    public function test_old_two_argument_constructor_uses_documented_delete_endpoint_style_without_manual_config_argument(): void
    {
        config()->set('vandar.iban.delete_endpoint_style', 'documented');

        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        $this->makeIbanResourceWithOldConstructor()->delete('fake-customer-id', 'fake-iban');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans'
            && $request['iban'] === 'fake-iban');
    }

    public function test_path_delete_endpoint_style_uses_customer_iban_endpoint(): void
    {
        config()->set('vandar.iban.delete_endpoint_style', 'path');

        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        Vandar::ibans()->delete('fake-customer-id', 'fake-iban');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_documented_delete_endpoint_style_uses_customer_ibans_endpoint_with_body(): void
    {
        config()->set('vandar.iban.delete_endpoint_style', 'documented');

        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        Vandar::ibans()->delete('fake-customer-id', 'fake-iban');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans'
            && $request['iban'] === 'fake-iban'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_invalid_delete_endpoint_style_safely_uses_path_endpoint(): void
    {
        config()->set('vandar.iban.delete_endpoint_style', 'unknown-style');

        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        Vandar::ibans()->delete('fake-customer-id', 'fake-iban');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban');
    }

    public function test_inquiry_calls_customer_iban_inquiry_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::ibans()->inquiry('fake-customer-id', 'fake-iban', [
            'first_name' => 'Fake',
            'last_name' => 'User',
            'track_id' => 'fake-track-id',
        ]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban/inquiry'
            && $request['track_id'] === 'fake-track-id');
    }

    public function test_set_default_calls_customer_iban_set_default_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::ibans()->setDefault('fake-customer-id', 'fake-iban');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/fake-customer-id/ibans/fake-iban/set-default');
    }

    public function test_explicit_business_argument_overrides_config(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::ibans()->list('fake-customer-id', business: 'explicit-business');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.vandar.io/v3/business/explicit-business/customers/fake-customer-id/ibans'));
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        Vandar::ibans()->list('fake-customer-id');
    }

    public function test_dynamic_customer_and_iban_segments_are_encoded(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        Vandar::ibans()->delete('customer id/branch', 'fake iban/branch');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v3/business/test-business/customers/customer%20id%2Fbranch/ibans/fake%20iban%2Fbranch');
    }

    public function test_authorization_header_is_attached_when_token_exists(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::ibans()->list('fake-customer-id');

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public function test_failed_response_does_not_throw_automatically(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['message' => 'Failed'], 422)]);

        $response = Vandar::ibans()->create('fake-customer-id', ['iban' => 'fake-iban']);

        $this->assertInstanceOf(VandarResponse::class, $response);
        $this->assertSame(422, $response->status());
    }

    private function makeIbanResourceWithOldConstructor(): IbanResource
    {
        return new IbanResource(
            $this->app->make(VandarClient::class),
            $this->app->make(BusinessResolver::class),
        );
    }
}
