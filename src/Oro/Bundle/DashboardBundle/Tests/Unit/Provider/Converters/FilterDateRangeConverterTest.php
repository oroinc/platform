<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilterDateRangeConverterTest extends \PHPUnit\Framework\TestCase
{
    private DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject $formatter;

    private Compiler|\PHPUnit\Framework\MockObject\MockObject $dateCompiler;

    private FilterDateRangeConverter $converter;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->dateCompiler = $this->createMock(Compiler::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $this->converter = new FilterDateRangeConverter(
            $this->formatter,
            $this->dateCompiler,
            $translator
        );
    }

    public function testGetConvertedValueDefaultValuesWithValueTypes(): void
    {
        $this->dateCompiler->expects($this->once())
            ->method('compile')
            ->with('{{4}}')
            ->willReturn(new \DateTime('01-01-2016 00:00:00'));
        $result = $this->converter->getConvertedValue([], null, ['options' => ['value_types' => true]]);

        self::assertEquals('2016-01-01 00:00:00', $result['start']->format('Y-m-d H:i:s'));
        self::assertEquals('2016-02-01 00:00:00', $result['end']->format('Y-m-d H:i:s'));
        self::assertEquals(\DateInterval::createFromDateString('1 day'), $result['last_second_modifier']);
    }

    /**
     * @dataProvider getConvertedValueDataProvider
     */
    public function testGetConvertedValue($value, array $config, array $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->converter->getConvertedValue([], $value, $config));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConvertedValueDataProvider(): array
    {
        return [
            'default values without value_types' => [
                'value' => null,
                'config' => [],
                'expectedResult' => [
                    'start' => null,
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    'part' => DateModifierInterface::PART_ALL_TIME,
                    'prev_start' => null,
                    'prev_end' => null,
                ],
            ],
            'between to all time' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'config' => [],
                'expectedResult' => [
                    'start' => null,
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    'part' => DateModifierInterface::PART_ALL_TIME,
                    'prev_start' => null,
                    'prev_end' => null,
                ],
            ],
            'between to more than' => [
                'value' => [
                    'value' => [
                        'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                        'end'   => null,
                    ],
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'config' => [],
                'expectedResult' => [
                    'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    'part' => DateModifierInterface::PART_VALUE,
                ],
            ],
            'between without end data and with save_open_range' => [
                'value' => [
                    'value' => [
                        'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                        'end'   => null,
                    ],
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'config' => [
                    'converter_attributes' => [
                        'save_open_range' => true,
                    ],
                ],
                'expectedResult' => [
                    'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => DateModifierInterface::PART_VALUE,
                ],
            ],
            'between to less than' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end'   => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'config' => [],
                'expectedResult' => [
                    'start' => new \DateTime(FilterDateRangeConverter::MIN_DATE, new \DateTimeZone('UTC')),
                    'end' => new \DateTime('2015-01-02 00:00:00', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_LESS_THAN,
                    'part' => DateModifierInterface::PART_VALUE,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                ],
            ],
            'between without start date and with save_open_range' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end'   => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'config' => [
                    'converter_attributes' => [
                        'save_open_range' => true,
                    ],
                ],
                'expectedResult' => [
                    'start' => null,
                    'end' => new \DateTime('2015-01-02 00:00:00', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => DateModifierInterface::PART_VALUE,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                ],
            ],
            'between' => [
                'value' => [
                    'value' => [
                        'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                        'end'   => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'config' => [],
                'expectedResult' => [
                    'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
                    'end' => new \DateTime('2015-01-02 00:00:00', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => DateModifierInterface::PART_VALUE,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                ],
            ],
            'between swaps start and end dates' => [
                'value' => [
                    'value' => [
                        'start' => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                        'end'   => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'config' => [],
                'expectedResult' => [
                    'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
                    'end' => new \DateTime('2015-01-02 00:00:00', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => DateModifierInterface::PART_VALUE,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                ],
            ],
            'between and create_previous_period' => [
                'value' => [
                    'value' => [
                        'start' => new \DateTime('2014-01-06', new \DateTimeZone('UTC')),
                        'end'   => new \DateTime('2014-01-07', new \DateTimeZone('UTC')),
                    ],
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'config' => [
                    'converter_attributes' => [
                        'create_previous_period' => true,
                    ],
                ],
                'expectedResult' => [
                    'start' => new \DateTime('2014-01-06 00:00:00', new \DateTimeZone('UTC')),
                    'end' => new \DateTime('2014-01-08 00:00:00', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => DateModifierInterface::PART_VALUE,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                    'prev_start' => new \DateTime('2014-01-04 00:00:00', new \DateTimeZone('UTC')),
                    'prev_end' => new \DateTime('2014-01-07 00:00:00', new \DateTimeZone('UTC')),
                ],
            ],
            'more than' => [
                'value' => [
                    'value' => [
                        'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                        'end'   => null,
                    ],
                    'type'  => AbstractDateFilterType::TYPE_MORE_THAN,
                ],
                'config' => [],
                'expectedResult' => [
                    'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    'part' => DateModifierInterface::PART_VALUE,
                ],
            ],
            'more than with save_open_range' => [
                'value' => [
                    'value' => [
                        'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                        'end'   => null,
                    ],
                    'type'  => AbstractDateFilterType::TYPE_MORE_THAN,
                ],
                'config' => [
                    'converter_attributes' => [
                        'save_open_range' => true,
                    ],
                ],
                'expectedResult' => [
                    'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => DateModifierInterface::PART_VALUE,
                ],
            ],
            'less than' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end'   => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type'  => AbstractDateFilterType::TYPE_LESS_THAN,
                ],
                'config' => [],
                'expectedResult' => [
                    'start' => new \DateTime(FilterDateRangeConverter::MIN_DATE, new \DateTimeZone('UTC')),
                    'end' => new \DateTime('2015-01-02 00:00:00', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_LESS_THAN,
                    'part' => DateModifierInterface::PART_VALUE,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                ],
            ],
            'less than with save_open_range' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end'   => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type'  => AbstractDateFilterType::TYPE_LESS_THAN,
                ],
                'config' => [
                    'converter_attributes' => [
                        'save_open_range' => true,
                    ],
                ],
                'expectedResult' => [
                    'start' => null,
                    'end' => new \DateTime('2015-01-02 00:00:00', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => DateModifierInterface::PART_VALUE,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider getViewValueDataProvider
     */
    public function testGetViewValue(array $dateData, string $expectedResult): void
    {
        $this->formatter->expects($this->exactly(2))
            ->method('formatDate')
            ->willReturnCallback(function ($input) {
                return $input->format('Y-m-d');
            });

        self::assertEquals(
            $expectedResult,
            $this->converter->getViewValue($dateData)
        );
    }

    public function getViewValueDataProvider(): array
    {
        return [
            'without "last second of the day" modifier' => [
                'dateData' => [
                    'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                    'end' => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => null,
                ],
                'expectedResult' => '2014-01-01 - 2015-01-01',
            ],
            'with "last second of the day" modifier' => [
                'dateData' => [
                    'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                    'end' => new \DateTime('2015-01-02', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'part' => null,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                ],
                'expectedResult' => '2014-01-01 - 2015-01-01',
            ],
        ];
    }
}
