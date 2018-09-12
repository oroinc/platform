<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Formatter;

use Oro\Component\PhpUtils\Formatter\BytesFormatter;

class BytesFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider formatBytesProvider
     *
     * @param string $expectedValue
     * @param int $value
     */
    public function testFormat($expectedValue, $value)
    {
        $this->assertEquals($expectedValue, BytesFormatter::format($value));
    }

    /**
     * @return array
     */
    public function formatBytesProvider()
    {
        return [
            ['-1.00 MB', -pow(1000, 2)],
            ['-1.00 B', -1],
            ['0.00 B', 0],
            ['1.00 B', 1],
            ['1.00 KB', 1000],
            ['1.00 MB', pow(1000, 2)],
            ['1.00 GB', pow(1000, 3)]
        ];
    }
}
