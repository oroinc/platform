<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeValueFormatter;

class DateTimeValueFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeValueFormatter */
    protected $formatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $datetimeFormatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function setUp()
    {
        $this->datetimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();

        $this->formatter = new DateTimeValueFormatter(
            $this->datetimeFormatter,
            $this->translator
        );
    }

    public function testGetFormatterName()
    {
        $this->assertEquals('datetime', $this->formatter->getFormatterName());
    }

    public function testFormat()
    {
        $parameter = new \DateTime();
        $this->datetimeFormatter
            ->expects($this->once())
            ->method('format')
            ->with($parameter);
        $this->formatter->format($parameter);
    }

    public function testGetSupportedTypes()
    {
        $this->assertEquals(['datetime'], $this->formatter->getSupportedTypes());
    }

    public function testIsDefaultFormatter()
    {
        $this->assertTrue($this->formatter->isDefaultFormatter());
    }

    public function testGetDefaultValue()
    {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('oro.locale.formatter.datetime.default');
        $this->formatter->getDefaultValue();
    }
}
