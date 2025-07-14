<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\PhpIniUtil;
use PHPUnit\Framework\TestCase;

class PhpIniUtilTest extends TestCase
{
    /**
     * @dataProvider parseBytesProvider
     */
    public function testParseBytes($value, $expectedValue): void
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
            ['1M', (float) 1024 ** 2],
            ['1m', (float) 1024 ** 2],
            ['1G', (float) 1024 ** 3],
            ['1g', (float) 1024 ** 3],
        ];
    }
}
