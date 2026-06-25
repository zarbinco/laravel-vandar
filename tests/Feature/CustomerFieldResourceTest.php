<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Exceptions\VandarBusinessNotConfiguredException;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class CustomerFieldResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vandar.business', 'test-business');
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');
    }

    public function test_list_calls_customer_fields_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::customers()->fields()->list();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fields');
    }

    public function test_create_calls_customer_fields_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['id' => 'fake-field-id'], 201)]);

        Vandar::customers()->fields()->create(['name' => 'Test Field']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fields'
            && $request['name'] === 'Test Field');
    }

    public function test_update_calls_customer_field_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->fields()->update('fake-field-id', ['name' => 'Updated Field']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fields/fake-field-id'
            && $request['name'] === 'Updated Field');
    }

    public function test_delete_calls_customer_field_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response([], 204)]);

        Vandar::customers()->fields()->delete('fake-field-id');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fields/fake-field-id');
    }

    public function test_find_and_show_call_customer_field_endpoint(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['ok' => true], 200)]);

        Vandar::customers()->fields()->find('fake-field-id');
        Vandar::customers()->fields()->show('fake-field-id');

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.vandar.io/v2/business/test-business/customers/fields/fake-field-id');
    }

    public function test_explicit_business_argument_works(): void
    {
        Http::fake(['https://api.vandar.io/*' => Http::response(['data' => []], 200)]);

        Vandar::customers()->fields()->list(business: 'explicit-business');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v2/business/explicit-business/customers/fields');
    }

    public function test_missing_business_throws(): void
    {
        config()->set('vandar.business', null);

        $this->expectException(VandarBusinessNotConfiguredException::class);

        Vandar::customers()->fields()->list();
    }
}
