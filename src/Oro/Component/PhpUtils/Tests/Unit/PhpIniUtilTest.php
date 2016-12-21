<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\PhpIniUtil;

class PhpIniUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider parseBytesProvider
     */
    public function testParseBytes($value, $expectedValue)
    {
        $this->assertEquals($expectedValue, PhpIniUtil::parseBytes($value));
    }

    public function parseBytesProvider()
    {
        return [
            ['-1', -1],
            ['1', 1],
            ['1B', 1],
            ['1b', 1],
            ['1K', 1024],
            ['1k', 1024],
            ['1M', pow(1024, 2)],
            ['1m', pow(1024, 2)],
            ['1G', pow(1024, 3)],
            ['1g', pow(1024, 3)],
        ];
    }
}
