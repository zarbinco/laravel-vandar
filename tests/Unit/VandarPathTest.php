<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zarbinco\LaravelVandar\Support\VandarPath;

final class VandarPathTest extends TestCase
{
    public function test_segment_encodes_unsafe_path_characters(): void
    {
        $this->assertSame('business%20name%2Fbranch', VandarPath::segment('business name/branch'));
    }

    public function test_join_avoids_duplicate_slashes(): void
    {
        $this->assertSame('/v2/business/test/customers', VandarPath::join('/v2/business/', '/test/', 'customers'));
    }

    public function test_join_preserves_endpoint_slash_structure(): void
    {
        $this->assertSame('/v2/business/test/customers/fields', VandarPath::join('v2/business', 'test', 'customers/fields'));
    }

    public function test_numeric_segment_works(): void
    {
        $this->assertSame('123', VandarPath::segment(123));
    }

    public function test_empty_segments_are_ignored_safely(): void
    {
        $this->assertSame('/v2/business/test', VandarPath::join('', '/v2/business/', '', 'test'));
    }
}
