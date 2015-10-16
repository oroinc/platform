<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\Translator;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Formatter\DateTimeTypeFormatter;

class DateTimeTypeFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateTimeTypeFormatter
     */
    protected $formatter;

    public function setUp()
    {
        /** @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Translator|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator      = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new DateTimeTypeFormatter($localeSettings, $translator);
    }

    /**
     * @dataProvider testFormatTypeProvider
     * @param string          $value
     * @param string          $type
     * @param \Exception|null $exception
     */
    public function testFormatType($value, $type, \Exception $exception = null)
    {
        if (null !== $exception) {
            $this->setExpectedException(get_class($exception), $exception->getMessage());
        }
        $this->formatter->formatType($value, $type);
    }

    /**
     * @return array
     */
    public function testFormatTypeProvider()
    {
        $value = (new \DateTime())->format('d/m/Y H:i:s');

        return [
            'type datetime'           => [$value, DateTimeTypeFormatter::TYPE_DATETIME],
            'type date'               => [$value, DateTimeTypeFormatter::TYPE_DATETIME],
            'type time'               => [$value, DateTimeTypeFormatter::TYPE_DATETIME],
            'type not supported type' => [
                $value,
                'test',
                new InvalidArgumentException('Couldn\'t format "test" type')
            ],
        ];
    }
}
