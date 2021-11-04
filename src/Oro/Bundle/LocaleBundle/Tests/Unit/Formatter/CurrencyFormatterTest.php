<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\CurrencyFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class CurrencyFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var CurrencyFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

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

    public function testGetDefaultValue()
    {
        $this->assertEquals(0, $this->formatter->getDefaultValue());
    }
}
