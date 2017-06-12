<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\DateValueFormatter;

class DateValueFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var DateValueFormatter */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datetimeFormatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    protected function setUp()
    {
        $this->datetimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();

        $this->formatter = new DateValueFormatter(
            $this->datetimeFormatter,
            $this->translator
        );
    }

    public function testGetFormatterName()
    {
        $this->assertEquals('date', $this->formatter->getFormatterName());
    }

    public function testFormat()
    {
        $parameter = new \DateTime();
        $this->datetimeFormatter
            ->expects($this->once())
            ->method('formatDate')
            ->with($parameter)
            ->will($this->returnValue('01 Jan 2016'));
        $this->assertEquals('01 Jan 2016', $this->formatter->format($parameter));
    }

    public function testGetSupportedTypes()
    {
        $this->assertEquals(['date'], $this->formatter->getSupportedTypes());
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
            ->with('oro.locale.formatter.datetime.default')
            ->will($this->returnValue('F y, j'));
        $this->assertEquals('F y, j', $this->formatter->getDefaultValue());
    }
}
