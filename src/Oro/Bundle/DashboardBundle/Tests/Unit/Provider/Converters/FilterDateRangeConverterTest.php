<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Carbon\Carbon;
use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FilterDateRangeConverterTest extends TestCase
{
    private static array $valueTypesStartVarsMap = [
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

    private DateTimeFormatterInterface&MockObject $formatter;
    private Compiler&MockObject $dateCompiler;
    private FilterDateRangeConverter $converter;

    #[\Override]
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

    #[\Override]
    protected function tearDown(): void
    {
        // Reset frozen time to avoid affecting other tests
        Carbon::setTestNow(null);
    }

    public function testGetConvertedValueDefaultValuesWithValueTypes(): void
    {
        $this->dateCompiler->expects(self::once())
            ->method('compile')
            ->with('{{4}}')
            ->willReturn(new \DateTime('01-01-2016 00:00:00'));

        $result = $this->converter->getConvertedValue([], null, ['options' => ['value_types' => true]]);

        self::assertEquals('2016-01-01 00:00:00', $result['start']->format('Y-m-d H:i:s'));
        self::assertEquals('2016-02-01 00:00:00', $result['end']->format('Y-m-d H:i:s'));
        self::assertEquals(\DateInterval::createFromDateString('1 day'), $result['last_second_modifier']);
    }

    public function testGetConvertedValueTypeToday(): void
    {
        $today = new \DateTime('2024-06-15 00:00:00', new \DateTimeZone('UTC'));
        $this->dateCompiler->expects(self::once())
            ->method('compile')
            ->with('{{' . DateModifierInterface::VAR_TODAY . '}}')
            ->willReturn($today);

        $value = [
            'value' => ['start' => null, 'end' => null],
            'type' => AbstractDateFilterType::TYPE_TODAY,
        ];

        $result = $this->converter->getConvertedValue([], $value, []);

        self::assertEquals('2024-06-15 00:00:00', $result['start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-06-16 00:00:00', $result['end']->format('Y-m-d H:i:s'));
        self::assertEquals(AbstractDateFilterType::TYPE_TODAY, $result['type']);
        self::assertNull($result['part']);
        self::assertEquals(\DateInterval::createFromDateString('1 day'), $result['last_second_modifier']);
    }

    public function testGetConvertedValueTypeTodayWithCreatePreviousPeriod(): void
    {
        $today = new \DateTime('2024-06-15 00:00:00', new \DateTimeZone('UTC'));
        $this->dateCompiler->expects(self::once())
            ->method('compile')
            ->with('{{' . DateModifierInterface::VAR_TODAY . '}}')
            ->willReturn($today);

        $value = [
            'value' => ['start' => null, 'end' => null],
            'type' => AbstractDateFilterType::TYPE_TODAY,
        ];
        $config = [
            'converter_attributes' => [
                'create_previous_period' => true,
            ],
        ];

        $result = $this->converter->getConvertedValue([], $value, $config);

        self::assertEquals('2024-06-15 00:00:00', $result['start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-06-16 00:00:00', $result['end']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-06-14 00:00:00', $result['prev_start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-06-15 00:00:00', $result['prev_end']->format('Y-m-d H:i:s'));
    }

    public function testGetConvertedValueThisWeekWithCreatePreviousPeriod(): void
    {
        // Monday of a week
        $startOfWeek = new \DateTime('2024-06-10 00:00:00', new \DateTimeZone('UTC'));
        $this->dateCompiler->expects(self::once())
            ->method('compile')
            ->with('{{' . DateModifierInterface::VAR_SOW . '}}')
            ->willReturn($startOfWeek);

        $value = [
            'value' => ['start' => null, 'end' => null],
            'type' => AbstractDateFilterType::TYPE_THIS_WEEK,
        ];
        $config = [
            'converter_attributes' => [
                'create_previous_period' => true,
            ],
        ];

        $result = $this->converter->getConvertedValue([], $value, $config);

        // Current week: Mon Jun 10 - Sun Jun 16
        self::assertEquals('2024-06-10 00:00:00', $result['start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-06-17 00:00:00', $result['end']->format('Y-m-d H:i:s'));
        // Previous week: Mon Jun 3 - Sun Jun 9
        self::assertEquals('2024-06-03 00:00:00', $result['prev_start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-06-10 00:00:00', $result['prev_end']->format('Y-m-d H:i:s'));
    }

    public function testGetConvertedValueThisMonthWithCreatePreviousPeriod(): void
    {
        $startOfMonth = new \DateTime('2024-06-01 00:00:00', new \DateTimeZone('UTC'));
        $this->dateCompiler->expects(self::once())
            ->method('compile')
            ->with('{{' . DateModifierInterface::VAR_SOM . '}}')
            ->willReturn($startOfMonth);

        $value = [
            'value' => ['start' => null, 'end' => null],
            'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
        ];
        $config = [
            'converter_attributes' => [
                'create_previous_period' => true,
            ],
        ];

        $result = $this->converter->getConvertedValue([], $value, $config);

        // Current month: Jun 1 - Jun 30
        self::assertEquals('2024-06-01 00:00:00', $result['start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-07-01 00:00:00', $result['end']->format('Y-m-d H:i:s'));
        // Previous month: May 1 - May 31
        self::assertEquals('2024-05-01 00:00:00', $result['prev_start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-06-01 00:00:00', $result['prev_end']->format('Y-m-d H:i:s'));
    }

    public function testGetConvertedValueThisQuarterWithCreatePreviousPeriod(): void
    {
        // Q2 starts April 1
        $startOfQuarter = new \DateTime('2024-04-01 00:00:00', new \DateTimeZone('UTC'));
        $this->dateCompiler->expects(self::once())
            ->method('compile')
            ->with('{{' . DateModifierInterface::VAR_SOQ . '}}')
            ->willReturn($startOfQuarter);

        $value = [
            'value' => ['start' => null, 'end' => null],
            'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
        ];
        $config = [
            'converter_attributes' => [
                'create_previous_period' => true,
            ],
        ];

        $result = $this->converter->getConvertedValue([], $value, $config);

        // Current quarter Q2: Apr 1 - Jun 30
        self::assertEquals('2024-04-01 00:00:00', $result['start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-07-01 00:00:00', $result['end']->format('Y-m-d H:i:s'));
        // Previous quarter Q1: Jan 1 - Mar 31
        self::assertEquals('2024-01-01 00:00:00', $result['prev_start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-04-01 00:00:00', $result['prev_end']->format('Y-m-d H:i:s'));
    }

    public function testGetConvertedValueThisYearWithCreatePreviousPeriod(): void
    {
        $startOfYear = new \DateTime('2024-01-01 00:00:00', new \DateTimeZone('UTC'));
        $this->dateCompiler->expects(self::once())
            ->method('compile')
            ->with('{{' . DateModifierInterface::VAR_SOY . '}}')
            ->willReturn($startOfYear);

        $value = [
            'value' => ['start' => null, 'end' => null],
            'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
        ];
        $config = [
            'converter_attributes' => [
                'create_previous_period' => true,
            ],
        ];

        $result = $this->converter->getConvertedValue([], $value, $config);

        // Current year: Jan 1, 2024 - Dec 31, 2024
        self::assertEquals('2024-01-01 00:00:00', $result['start']->format('Y-m-d H:i:s'));
        self::assertEquals('2025-01-01 00:00:00', $result['end']->format('Y-m-d H:i:s'));
        // Previous year: Jan 1, 2023 - Dec 31, 2023
        self::assertEquals('2023-01-01 00:00:00', $result['prev_start']->format('Y-m-d H:i:s'));
        self::assertEquals('2024-01-01 00:00:00', $result['prev_end']->format('Y-m-d H:i:s'));
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

    public function testGetConvertedValueMoreThanWithTodayAsEndDateFor(): void
    {
        // Freeze time to completely eliminate race conditions around midnight UTC
        Carbon::setTestNow(Carbon::create(2025, 6, 15, 12, 0, 0, 'UTC'));

        $value = [
            'value' => [
                'start' => new \DateTime('2014-01-01', new \DateTimeZone('UTC')),
                'end' => null,
            ],
            'type' => AbstractDateFilterType::TYPE_MORE_THAN,
        ];
        $config = [
            'converter_attributes' => [
                'today_as_end_date_for' => ['TYPE_MORE_THAN'],
            ],
        ];

        $lastSecondModifier = \DateInterval::createFromDateString('1 day');
        $expectedEnd = Carbon::today(new \DateTimeZone('UTC'))->add($lastSecondModifier);

        $expectedResult = [
            'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
            'end' => $expectedEnd,
            'type' => AbstractDateFilterType::TYPE_MORE_THAN,
            'part' => DateModifierInterface::PART_VALUE,
            'last_second_modifier' => $lastSecondModifier,
        ];

        self::assertEquals($expectedResult, $this->converter->getConvertedValue([], $value, $config));
    }

    /**
     * @dataProvider getConvertedValueThisRangeEndDateDateProvider
     */
    public function testGetConvertedValueThisRangeEndDate(
        array $value,
        array $config,
        ?string $expectedEndDateModifier
    ): void {
        // Freeze time to completely eliminate race conditions around midnight UTC
        Carbon::setTestNow(Carbon::create(2025, 6, 15, 12, 0, 0, 'UTC'));

        $today = Carbon::today(new \DateTimeZone('UTC'));
        if (array_key_exists($value['type'], self::$valueTypesStartVarsMap)) {
            $this->dateCompiler->expects(self::once())
                ->method('compile')
                ->with('{{' . self::$valueTypesStartVarsMap[$value['type']]['var_start'] . '}}')
                ->willReturn($today);
        }

        // Compute expected end date based on the modifier
        if (null === $expectedEndDateModifier) {
            $expectedEndDate = Carbon::today(new \DateTimeZone('UTC'));
        } else {
            $expectedEndDate = Carbon::today(new \DateTimeZone('UTC'))->modify($expectedEndDateModifier);
        }

        $lastSecondModifier = \DateInterval::createFromDateString('1 day');
        $expectedEndDate->setTime(0, 0)->add($lastSecondModifier);

        $expectedResult = [
            'start' => AbstractDateFilterType::TYPE_ALL_TIME === $value['type'] ? null : $today,
            'end' => $expectedEndDate,
            'type' => $value['type'],
            'part' => AbstractDateFilterType::TYPE_ALL_TIME === $value['type']
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
        // Return modifier strings instead of DateTime objects to avoid race conditions around midnight UTC.
        // The test method will compute the actual DateTime at test execution time.
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
                'expectedEndDateModifier' =>
                    self::$valueTypesStartVarsMap[AbstractDateFilterType::TYPE_THIS_WEEK]['modify_end'],
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
                'expectedEndDateModifier' => null,
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
                'expectedEndDateModifier' =>
                    self::$valueTypesStartVarsMap[AbstractDateFilterType::TYPE_THIS_MONTH]['modify_end'],
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
                'expectedEndDateModifier' => null,
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
                'expectedEndDateModifier' =>
                    self::$valueTypesStartVarsMap[AbstractDateFilterType::TYPE_THIS_QUARTER]['modify_end'],
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
                'expectedEndDateModifier' => null,
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
                'expectedEndDateModifier' =>
                    self::$valueTypesStartVarsMap[AbstractDateFilterType::TYPE_THIS_YEAR]['modify_end'],
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
                'expectedEndDateModifier' => null,
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
                'expectedEndDateModifier' => null,
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

    public function testGetViewValueAllTime(): void
    {
        $dateData = [
            'start' => null,
            'end' => null,
            'type' => AbstractDateFilterType::TYPE_ALL_TIME,
            'part' => DateModifierInterface::PART_ALL_TIME,
        ];

        self::assertEquals(
            'oro.dashboard.widget.filter.date_range.all_time',
            $this->converter->getViewValue($dateData)
        );
    }

    public function testGetViewValueThisMonth(): void
    {
        $start = new \DateTime('2024-03-01', new \DateTimeZone('UTC'));
        $this->formatter->expects(self::once())
            ->method('formatMonth')
            ->with($start)
            ->willReturn('March 2024');

        $dateData = [
            'start' => $start,
            'end' => new \DateTime('2024-04-01', new \DateTimeZone('UTC')),
            'type' => AbstractDateFilterType::TYPE_THIS_MONTH,
            'part' => null,
        ];

        self::assertEquals('March 2024', $this->converter->getViewValue($dateData));
    }

    public function testGetViewValueThisQuarter(): void
    {
        $start = new \DateTime('2024-01-01', new \DateTimeZone('UTC'));
        $this->formatter->expects(self::once())
            ->method('formatQuarter')
            ->with($start)
            ->willReturn('Q1 2024');

        $dateData = [
            'start' => $start,
            'end' => new \DateTime('2024-04-01', new \DateTimeZone('UTC')),
            'type' => AbstractDateFilterType::TYPE_THIS_QUARTER,
            'part' => null,
        ];

        self::assertEquals('Q1 2024', $this->converter->getViewValue($dateData));
    }

    public function testGetViewValueThisYear(): void
    {
        $start = new \DateTime('2024-01-01', new \DateTimeZone('UTC'));
        $this->formatter->expects(self::once())
            ->method('formatYear')
            ->with($start)
            ->willReturn('2024');

        $dateData = [
            'start' => $start,
            'end' => new \DateTime('2025-01-01', new \DateTimeZone('UTC')),
            'type' => AbstractDateFilterType::TYPE_THIS_YEAR,
            'part' => null,
        ];

        self::assertEquals('2024', $this->converter->getViewValue($dateData));
    }

    public function testGetViewValueMoreThan(): void
    {
        $start = new \DateTime('2024-01-01', new \DateTimeZone('UTC'));
        $this->formatter->expects(self::once())
            ->method('formatDate')
            ->with($start)
            ->willReturn('Jan 1, 2024');

        $dateData = [
            'start' => $start,
            'end' => null,
            'type' => AbstractDateFilterType::TYPE_MORE_THAN,
            'part' => DateModifierInterface::PART_VALUE,
        ];

        self::assertEquals(
            'oro.filter.form.label_date_type_more_than Jan 1, 2024',
            $this->converter->getViewValue($dateData)
        );
    }

    public function testGetViewValueLessThan(): void
    {
        $end = new \DateTime('2024-12-31', new \DateTimeZone('UTC'));
        $this->formatter->expects(self::once())
            ->method('formatDate')
            ->with($end)
            ->willReturn('Dec 31, 2024');

        $dateData = [
            'start' => null,
            'end' => $end,
            'type' => AbstractDateFilterType::TYPE_LESS_THAN,
            'part' => DateModifierInterface::PART_VALUE,
        ];

        self::assertEquals(
            'oro.filter.form.label_date_type_less_than Dec 31, 2024',
            $this->converter->getViewValue($dateData)
        );
    }

    public function testGetViewValueBetweenWithoutEnd(): void
    {
        $start = new \DateTime('2024-01-01', new \DateTimeZone('UTC'));
        $this->formatter->expects(self::once())
            ->method('formatDate')
            ->with($start)
            ->willReturn('Jan 1, 2024');

        $dateData = [
            'start' => $start,
            'end' => null,
            'type' => AbstractDateFilterType::TYPE_BETWEEN,
            'part' => DateModifierInterface::PART_VALUE,
        ];

        self::assertEquals(
            'oro.filter.form.label_date_type_more_than Jan 1, 2024',
            $this->converter->getViewValue($dateData)
        );
    }

    public function testGetViewValueBetweenWithoutStart(): void
    {
        $end = new \DateTime('2024-12-31', new \DateTimeZone('UTC'));
        $this->formatter->expects(self::once())
            ->method('formatDate')
            ->with($end)
            ->willReturn('Dec 31, 2024');

        $dateData = [
            'start' => null,
            'end' => $end,
            'type' => AbstractDateFilterType::TYPE_BETWEEN,
            'part' => DateModifierInterface::PART_VALUE,
        ];

        self::assertEquals(
            'oro.filter.form.label_date_type_less_than Dec 31, 2024',
            $this->converter->getViewValue($dateData)
        );
    }

    public function testGetViewValueSameStartAndEndDate(): void
    {
        $this->formatter->expects(self::exactly(2))
            ->method('formatDate')
            ->willReturn('Jan 1, 2024');

        $dateData = [
            'start' => new \DateTime('2024-01-01', new \DateTimeZone('UTC')),
            'end' => new \DateTime('2024-01-01', new \DateTimeZone('UTC')),
            'type' => AbstractDateFilterType::TYPE_BETWEEN,
            'part' => null,
        ];

        self::assertEquals('Jan 1, 2024', $this->converter->getViewValue($dateData));
    }

    /**
     * @dataProvider getViewValueDataProvider
     */
    public function testGetViewValue(array $dateData, string $expectedResult): void
    {
        $this->formatter->expects(self::exactly(2))
            ->method('formatDate')
            ->willReturnCallback(static fn ($input) => $input->format('Y-m-d'));

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
    public function testGetFormValue(array $config, $value, ?array $expectedValue): void
    {
        self::assertEquals(
            $expectedValue,
            $this->converter->getFormValue($config, $value)
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
                'config' => [],
                'value' => $value,
                'expectedValue' => $value,
            ],
            'value === null and no default value' => [
                'config' => [],
                'value' => null,
                'expectedValue' => null,
            ],
            'value === null and default value' => [
                'config' => [
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
