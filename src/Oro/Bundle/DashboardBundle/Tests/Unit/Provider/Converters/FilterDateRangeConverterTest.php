<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Carbon\Carbon;
use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilterDateRangeConverterTest extends \PHPUnit\Framework\TestCase
{
    private static $valueTypesStartVarsMap = [
        AbstractDateFilterType::TYPE_TODAY => [
            'var_start' => DateModifierInterface::VAR_TODAY,
            'modify_end' => null,
            'modify_previous_start' => '- 1 day'
        ],
        AbstractDateFilterType::TYPE_THIS_WEEK => [
            'var_start' => DateModifierInterface::VAR_SOW,
            'modify_end' => '+ 1 week - 1 day',
            'modify_previous_start' => '- 1 week'
        ],
        AbstractDateFilterType::TYPE_THIS_MONTH => [
            'var_start' => DateModifierInterface::VAR_SOM,
            'modify_end' => '+ 1 month  - 1 day',
            'modify_previous_start' => '- 1 month'
        ],
        AbstractDateFilterType::TYPE_THIS_QUARTER => [
            'var_start' => DateModifierInterface::VAR_SOQ,
            'modify_end' => '+ 3 month - 1 day',
            'modify_previous_start' => '- 3 month'
        ],
        AbstractDateFilterType::TYPE_THIS_YEAR => [
            'var_start' => DateModifierInterface::VAR_SOY,
            'modify_end' => '+ 1 year - 1 day',
            'modify_previous_start' => '- 1 year'
        ],
    ];

    private DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject $formatter;

    private Compiler|\PHPUnit\Framework\MockObject\MockObject $dateCompiler;

    private FilterDateRangeConverter $converter;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->dateCompiler = $this->createMock(Compiler::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

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
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
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
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
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
                        'end' => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
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
                        'end' => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
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
                        'end' => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
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
                        'end' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
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
                        'end' => new \DateTime('2014-01-07', new \DateTimeZone('UTC')),
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
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
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN,
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
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN,
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
            'more than with today_as_end_date_for' => [
                'value' => [
                    'value' => [
                        'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                ],
                'config' => [
                    'converter_attributes' => [
                        'today_as_end_date_for' => ['TYPE_MORE_THAN'],
                    ],
                ],
                'expectedResult' => [
                    'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
                    'end' => new \DateTime(FilterDateRangeConverter::TODAY . ' + 1 day', new \DateTimeZone('UTC')),
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    'part' => DateModifierInterface::PART_VALUE,
                    'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
                ],
            ],
            'less than' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type' => AbstractDateFilterType::TYPE_LESS_THAN,
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
                        'end' => new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                    ],
                    'type' => AbstractDateFilterType::TYPE_LESS_THAN,
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
            'default selected' => [
                'value' => null,
                'config' => [
                    'converter_attributes' => [
                        'default_selected' => AbstractDateFilterType::TYPE_ALL_TIME,
                    ],
                ],
                'expectedResult' => [
                    'start' => null,
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                    'part' => DateModifierInterface::PART_ALL_TIME,
                    'prev_start' => null,
                    'prev_end' => null,
                ],
            ],
            'None value' => [
                'value' => null,
                'config' => [
                    'converter_attributes' => [
                        'default_selected' => AbstractDateFilterType::TYPE_NONE,
                    ],
                ],
                'expectedResult' => [
                    'start' => null,
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_NONE,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getConvertedValueThisRangeEndDateDateProvider
     */
    public function testGetConvertedValueThisRangeEndDate(
        array $value,
        array $config,
        \DateTimeInterface $expectedEndDate
    ): void {
        $today = new \DateTime(FilterDateRangeConverter::TODAY, new \DateTimeZone('UTC'));
        if (array_key_exists($value['type'], static::$valueTypesStartVarsMap)) {
            $this->dateCompiler->expects($this->once())
                ->method('compile')
                ->with('{{' . self::$valueTypesStartVarsMap[$value['type']]['var_start'] . '}}')
                ->willReturn($today);
        }

        $lastSecondModifier = \DateInterval::createFromDateString('1 day');
        $expectedEndDate->setTime(0, 0)->add($lastSecondModifier);

        $expectedResult = [
            'start' => $value['type'] === AbstractDateFilterType::TYPE_ALL_TIME ? null : $today,
            'end' => $expectedEndDate,
            'type' => $value['type'],
            'part' => $value['type'] === AbstractDateFilterType::TYPE_ALL_TIME
                ? DateModifierInterface::PART_ALL_TIME
                : null,
            'last_second_modifier' => \DateInterval::createFromDateString('1 day'),
            'prev_start' => null,
            'prev_end' => null,
        ];

        self::assertEquals($expectedResult, $this->converter->getConvertedValue([], $value, $config));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConvertedValueThisRangeEndDateDateProvider(): array
    {
        $today = new \DateTime(FilterDateRangeConverter::TODAY, new \DateTimeZone('UTC'));

        return [
            'this week' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_THIS_WEEK,
                ],
                'config' => [],
                'expectedEndDate' => new \DateTime(
                    FilterDateRangeConverter::TODAY
                    . self::$valueTypesStartVarsMap[AbstractDateFilterType::TYPE_THIS_WEEK]['modify_end'],
                    new \DateTimeZone('UTC')
                ),
            ],
            'this week with today_as_end_date_for' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_THIS_WEEK,
                ],
                'config' => [
                    'converter_attributes' => [
                        'today_as_end_date_for' => ['TYPE_THIS_WEEK'],
                    ],
                ],
                'expectedEndDate' => clone $today,
            ],
            'this month' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                ],
                'config' => [],
                'expectedEndDate' => new \DateTime(
                    FilterDateRangeConverter::TODAY
                    . self::$valueTypesStartVarsMap[AbstractDateFilterType::TYPE_THIS_MONTH]['modify_end'],
                    new \DateTimeZone('UTC')
                ),
            ],
            'this month with today_as_this_range_end_date' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                ],
                'config' => [
                    'converter_attributes' => [
                        'today_as_end_date_for' => ['TYPE_THIS_MONTH'],
                    ],
                ],
                'expectedEndDate' => clone $today,
            ],
            'this quarter' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                ],
                'config' => [],
                'expectedEndDate' => new \DateTime(
                    FilterDateRangeConverter::TODAY
                    . self::$valueTypesStartVarsMap[AbstractDateFilterType::TYPE_THIS_QUARTER]['modify_end'],
                    new \DateTimeZone('UTC')
                ),
            ],
            'this quarter with today_as_this_range_end_date' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                ],
                'config' => [
                    'converter_attributes' => [
                        'today_as_end_date_for' => ['TYPE_THIS_QUARTER'],
                    ],
                ],
                'expectedEndDate' => clone $today,
            ],
            'this year' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
                ],
                'config' => [],
                'expectedEndDate' => new \DateTime(
                    FilterDateRangeConverter::TODAY
                    . self::$valueTypesStartVarsMap[AbstractDateFilterType::TYPE_THIS_YEAR]['modify_end'],
                    new \DateTimeZone('UTC')
                ),
            ],
            'this year with today_as_this_range_end_date' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
                ],
                'config' => [
                    'converter_attributes' => [
                        'today_as_end_date_for' => ['TYPE_THIS_YEAR'],
                    ],
                ],
                'expectedEndDate' => clone $today,
            ],
            'all time with today_as_end_date_for' => [
                'value' => [
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                ],
                'config' => [
                    'converter_attributes' => [
                        'today_as_end_date_for' => ['TYPE_ALL_TIME'],
                    ],
                ],
                'expectedEndDate' => Carbon::today(new \DateTimeZone('UTC')),
            ],
        ];
    }

    public function testGetViewValueNone(): void
    {
        $this->formatter->expects(self::never())
            ->method('formatDate')
            ->withAnyParameters();

        $dateData = [
            'start' => null,
            'end' => null,
            'type' => AbstractDateFilterType::TYPE_NONE,
        ];

        self::assertEquals(
            'oro.dashboard.widget.filter.date_range.none',
            $this->converter->getViewValue($dateData)
        );
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

    /**
     * @dataProvider getFormValueDataProvider
     */
    public function testGetFormValue(array $converterAttributes, $value, ?array $expectedValue): void
    {
        self::assertEquals(
            $expectedValue,
            $this->converter->getFormValue($converterAttributes, $value)
        );
    }

    public function getFormValueDataProvider(): array
    {
        $value = [
            'type' => AbstractDateFilterType::TYPE_ALL_TIME,
            'value' => [
                'start' => null,
                'end' => null,
            ],
            'part' => 'value',
        ];
        $defaultSelectedType = AbstractDateFilterType::TYPE_NONE;
        $defaultValue = [
            'type' => $defaultSelectedType,
            'value' => [
                'start' => null,
                'end' => null,
            ],
            'part' => 'value',
        ];

        return [
            'as is' => [
                'converterAttributes' => [],
                'value' => $value,
                'expectedValue' => $value,
            ],
            'value === null and no default value' => [
                'converterAttributes' => [],
                'value' => null,
                'expectedValue' => null,
            ],
            'value === null and default value' => [
                'converterAttributes' => [
                    'converter_attributes' => [
                        'default_selected' => $defaultSelectedType,
                    ],
                ],
                'value' => null,
                'expectedValue' => $defaultValue,
            ],
        ];
    }
}
