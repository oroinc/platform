<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

use Oro\Bundle\ImportExportBundle\Formatter\NumberTypeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class NumberTypeFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NumberTypeFormatter
     */
    protected $formatter;

    public function setUp()
    {
        /** @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = new NumberTypeFormatter($localeSettings);
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
        $value = 1;

        return [
            'type currency'           => [$value, NumberTypeFormatter::TYPE_CURRENCY],
            'type decimal'            => [$value, NumberTypeFormatter::TYPE_DECIMAL],
            'type integer'            => [$value, NumberTypeFormatter::TYPE_INTEGER],
            'type percent'            => [$value, NumberTypeFormatter::TYPE_PERCENT],
            'type not supported type' => [
                $value,
                'test',
                new InvalidArgumentException('Couldn\'t format "test" type')
            ],
        ];
    }
}
