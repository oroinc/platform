<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\PhpIniUtil;

class PhpIniUtilTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider parseBytesProvider
     */
    public function testParseBytes($value, $expectedValue)
    {
        $this->assertSame($expectedValue, PhpIniUtil::parseBytes($value));
    }

    public function parseBytesProvider(): array
    {
        return [
            ['-1', -1.0],
            ['1', 1.0],
            ['1B', 1.0],
            ['1b', 1.0],
            ['1K', 1024.0],
            ['1k', 1024.0],
            ['1M', (float) pow(1024, 2)],
            ['1m', (float) pow(1024, 2)],
            ['1G', (float) pow(1024, 3)],
            ['1g', (float) pow(1024, 3)],
        ];
    }
}
