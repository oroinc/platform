<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\Version;

class VersionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider normalizeVersionDataProvider
     */
    public function testNormalizeVersion(?string $version, string $normalizedVersion)
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
