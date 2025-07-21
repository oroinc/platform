<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\CurrencyFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyFormatterTest extends TestCase
{
    private NumberFormatter&MockObject $numberFormatter;
    private CurrencyFormatter $formatter;

    #[\Override]
    protected function setUp(): void
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $this->formatter = new CurrencyFormatter($this->numberFormatter);
    }

    public function testFormatWithDefaultArguments(): void
    {
        $dateTime = new \DateTime();

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with($dateTime, null, [], [], [], null)
            ->willReturn('123.45');
        $this->assertEquals('123.45', $this->formatter->format($dateTime));
    }

    public function testFormat(): void
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

    public function testGetDefaultValue(): void
    {
        $this->assertEquals(0, $this->formatter->getDefaultValue());
    }
}
