<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    /**
     * @dataProvider normalizeVersionDataProvider
     */
    public function testNormalizeVersion(?string $version, string $normalizedVersion): void
    {
        self::assertSame($normalizedVersion, Version::normalizeVersion($version));
    }

    public function normalizeVersionDataProvider(): array
    {
        return [
            [null, 'latest'],
            ['latest', 'latest'],
            ['1.0', '1.0'],
            ['v1.0', '1.0']
        ];
    }
}
