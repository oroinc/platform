<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeValueFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeValueFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datetimeFormatter;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DateTimeValueFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->datetimeFormatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new DateTimeValueFormatter(
            $this->datetimeFormatter,
            $this->translator
        );
    }

    public function testFormat()
    {
        $parameter = new \DateTime();
        $this->datetimeFormatter->expects($this->once())
            ->method('format')
            ->with($parameter);
        $this->formatter->format($parameter);
    }

    public function testGetDefaultValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.locale.formatter.datetime.default');
        $this->formatter->getDefaultValue();
    }
}
