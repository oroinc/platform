<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Formatter;

use Oro\Bundle\SearchBundle\Formatter\DecimalFlatValueFormatter;
use PHPUnit\Framework\TestCase;

class DecimalFlatValueFormatterTest extends TestCase
{
    public function testFormat(): void
    {
        $formatter = new DecimalFlatValueFormatter();
        $this->assertSame('12.34', $formatter->format(12.34));
    }
}
