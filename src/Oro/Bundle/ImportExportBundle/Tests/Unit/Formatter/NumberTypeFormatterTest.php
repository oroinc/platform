<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Formatter\NumberTypeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class NumberTypeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NumberTypeFormatter
     */
    protected $formatter;

    public function setUp()
    {
        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $numberFormatter */
        $numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = new NumberTypeFormatter($numberFormatter);
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
