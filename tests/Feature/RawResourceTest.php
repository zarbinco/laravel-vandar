<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zarbinco\LaravelVandar\Facades\Vandar;
use Zarbinco\LaravelVandar\Tests\TestCase;

final class RawResourceTest extends TestCase
{
    public function test_facade_raw_resource_can_make_get_and_post_requests(): void
    {
        Http::fake([
            'https://api.vandar.io/*' => Http::sequence()
                ->push(['data' => ['pong' => true]], 200)
                ->push(['track_id' => 'fake-track-id'], 201),
        ]);

        $getResponse = Vandar::raw()->get('api', '/v2/ping', auth: false);
        $postResponse = Vandar::raw()->post('api', '/v2/manual', ['amount' => 1000], auth: false);

        $this->assertTrue($getResponse->successful());
        $this->assertSame(['pong' => true], $getResponse->data());
        $this->assertSame(201, $postResponse->status());
        $this->assertSame('fake-track-id', $postResponse->trackId());

        Http::assertSent(fn (Request $request): bool => in_array($request->method(), ['GET', 'POST'], true));
    }
}
