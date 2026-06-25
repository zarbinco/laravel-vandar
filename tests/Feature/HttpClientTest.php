<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\DTO\VandarResponse;
use Zarbinco\LaravelVandar\Exceptions\VandarValidationException;
use Zarbinco\LaravelVandar\Http\VandarClient;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class HttpClientTest extends TestCase
{
    public function test_get_and_post_call_the_configured_base_url(): void
    {
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');

        Http::fake([
            'https://api.vandar.io/*' => Http::response(['data' => ['ok' => true]], 200),
        ]);

        $client = $this->app->make(VandarClient::class);

        $getResponse = $client->get('api', '/v2/ping', ['hello' => 'world']);
        $postResponse = $client->post('api', 'v2/manual', ['amount' => 1000]);

        $this->assertInstanceOf(VandarResponse::class, $getResponse);
        $this->assertTrue($postResponse->successful());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://api.vandar.io/v2/ping')
            && $request->hasHeader('Authorization', 'Bearer fake-access-token'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.vandar.io/v2/manual'
            && $request['amount'] === 1000);
    }

    public function test_authorization_header_is_not_attached_when_auth_is_disabled(): void
    {
        config()->set('vandar.tokens.access_token', 'fake-access-token');
        config()->set('vandar.tokens.refresh_token', 'fake-refresh-token');

        Http::fake([
            'https://api.vandar.io/*' => Http::response(['ok' => true], 200),
        ]);

        $this->app->make(VandarClient::class)->get('api', '/v2/public', auth: false);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.vandar.io/v2/public'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_failed_response_does_not_throw_until_requested(): void
    {
        Http::fake([
            'https://api.vandar.io/*' => Http::response(['message' => 'Invalid payload'], 422),
        ]);

        $response = $this->app->make(VandarClient::class)->post('api', '/v2/manual', ['field' => 'bad'], auth: false);

        $this->assertTrue($response->failed());
        $this->assertSame(422, $response->status());

        $this->expectException(VandarValidationException::class);

        $response->throw();
    }
}
