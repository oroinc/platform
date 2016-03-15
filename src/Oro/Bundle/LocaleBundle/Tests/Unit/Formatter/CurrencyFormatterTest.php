<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\CurrencyFormatter;
use Oro\Bundle\LocaleBundle\Twig\NumberExtension;

class CurrencyFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurrencyFormatter
     */
    protected $formatter;

    /**
     * @var NumberExtension
     */
    protected $numberExtension;

    protected function setUp()
    {
        $this->numberExtension = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Twig\NumberExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new CurrencyFormatter($this->numberExtension);
    }

    public function testFormat()
    {
        $dateTime = new \DateTime();

        $this->numberExtension
            ->expects($this->once())
            ->method('formatCurrency')
            ->with($dateTime, [])
            ->willReturn('2000-01-01 00:00:00');
        $this->assertEquals('2000-01-01 00:00:00', $this->formatter->format($dateTime));
    }

    public function testGetSupportedTypes()
    {
        $this->assertEquals(['money'], $this->formatter->getSupportedTypes());
    }

    public function getIsDefaultFormatter()
    {
        $this->assertTrue($this->formatter->isDefaultFormatter());
    }

    public function testGetDefaultValue()
    {
        $this->assertEquals(0, $this->formatter->getDefaultValue());
    }

    public function testGetFormatterName()
    {
        $this->assertEquals('currency', $this->formatter->getFormatterName());
    }
}
