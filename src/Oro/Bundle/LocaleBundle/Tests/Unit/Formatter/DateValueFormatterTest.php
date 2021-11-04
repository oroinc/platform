<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateValueFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateValueFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datetimeFormatter;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DateValueFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->datetimeFormatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new DateValueFormatter(
            $this->datetimeFormatter,
            $this->translator
        );
    }

    public function testFormat()
    {
        $parameter = new \DateTime();
        $this->datetimeFormatter->expects($this->once())
            ->method('formatDate')
            ->with($parameter)
            ->willReturn('01 Jan 2016');
        $this->assertEquals('01 Jan 2016', $this->formatter->format($parameter));
    }

    public function testGetDefaultValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.locale.formatter.datetime.default')
            ->willReturn('F y, j');
        $this->assertEquals('F y, j', $this->formatter->getDefaultValue());
    }
}
