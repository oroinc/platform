<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Formatter;

use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use PHPUnit\Framework\TestCase;

class BytesFormatterTest extends TestCase
{
    /**
     * @dataProvider formatBytesProvider
     *
     * @param string $expectedValue
     * @param int $value
     */
    public function testFormat($expectedValue, $value): void
    {
        $this->assertEquals($expectedValue, BytesFormatter::format($value));
    }

    public function formatBytesProvider(): array
    {
        return [
            ['-1.00 MB', -1000 ** 2],
            ['-1.00 B', -1],
            ['0.00 B', 0],
            ['1.00 B', 1],
            ['1.00 KB', 1000],
            ['1.00 MB', 1000 ** 2],
            ['1.00 GB', 1000 ** 3]
        ];
    }
}
