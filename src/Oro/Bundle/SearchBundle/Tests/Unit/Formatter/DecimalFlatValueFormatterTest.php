<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Formatter;

use Oro\Bundle\SearchBundle\Formatter\DecimalFlatValueFormatter;

class DecimalFlatValueFormatterTest extends \PHPUnit\Framework\TestCase
{
    public function testFormat()
    {
        $formatter = new DecimalFlatValueFormatter();
        $this->assertSame('12.34', $formatter->format(12.34));
    }
}
