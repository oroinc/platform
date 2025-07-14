<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeValueFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeValueFormatterTest extends TestCase
{
    private DateTimeFormatterInterface&MockObject $datetimeFormatter;
    private TranslatorInterface&MockObject $translator;
    private DateTimeValueFormatter $formatter;

    #[\Override]
    protected function setUp(): void
    {
        $this->datetimeFormatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new DateTimeValueFormatter(
            $this->datetimeFormatter,
            $this->translator
        );
    }

    public function testFormat(): void
    {
        $parameter = new \DateTime();
        $this->datetimeFormatter->expects($this->once())
            ->method('format')
            ->with($parameter);
        $this->formatter->format($parameter);
    }

    public function testGetDefaultValue(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.locale.formatter.datetime.default');
        $this->formatter->getDefaultValue();
    }
}
