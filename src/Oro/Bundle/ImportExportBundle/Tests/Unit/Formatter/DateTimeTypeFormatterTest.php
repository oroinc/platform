<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Formatter\DateTimeTypeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Translation\Translator;

class DateTimeTypeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DateTimeTypeFormatter
     */
    protected $formatter;

    public function setUp()
    {
        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator      = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new DateTimeTypeFormatter($localeSettings, $translator);
    }

    /**
     * @dataProvider formatTypeProvider
     * @param string          $value
     * @param string          $type
     * @param \Exception|null $exception
     */
    public function testFormatType($value, $type, \Exception $exception = null)
    {
        if (null !== $exception) {
            $this->expectException(get_class($exception));
            $this->expectExceptionMessage($exception->getMessage());
        }
        $this->formatter->formatType($value, $type);
    }

    /**
     * @return array
     */
    public function formatTypeProvider()
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
