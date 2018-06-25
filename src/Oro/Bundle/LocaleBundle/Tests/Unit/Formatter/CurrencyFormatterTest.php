<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\CurrencyFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class CurrencyFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CurrencyFormatter */
    protected $formatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $numberFormatter;

    protected function setUp()
    {
        $this->numberFormatter = $this->getMockBuilder(NumberFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new CurrencyFormatter($this->numberFormatter);
    }

    public function testFormatWithDefaultArguments()
    {
        $dateTime = new \DateTime();

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with($dateTime, null, [], [], [], null)
            ->willReturn('123.45');
        $this->assertEquals('123.45', $this->formatter->format($dateTime));
    }

    public function testFormat()
    {
        $dateTime = new \DateTime();

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with($dateTime, 'USD', ['attr' => 'val'], ['textAttr' => 'val'], ['$'], 'en')
            ->willReturn('123.45');
        $this->assertEquals(
            '123.45',
            $this->formatter->format(
                $dateTime,
                [
                    'currency'       => 'USD',
                    'attributes'     => ['attr' => 'val'],
                    'textAttributes' => ['textAttr' => 'val'],
                    'symbols'        => ['$'],
                    'locale'         => 'en',
                ]
            )
        );
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
