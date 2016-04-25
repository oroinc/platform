<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\DateFilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

class DateFilterUtilityTest extends \PHPUnit_Framework_TestCase
{
    /** @var DateFilterUtility */
    protected $utility;

    protected function setUp()
    {
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(array('getTimezone', 'getFirstQuarterMonth', 'getFirstQuarterDay'))
            ->getMock();
        $compiler = $this->getMockBuilder('Oro\Bundle\FilterBundle\Expression\Date\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $expressionResult = $this->getMockBuilder('Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult')
            ->disableOriginalConstructor()
            ->getMock();
        $expressionResult->expects($this->any())
            ->method('getVariableType')
            ->will($this->returnValue(DateModifierInterface::VAR_THIS_DAY_W_Y));
        $compiler->expects($this->any())
            ->method('compile')
            ->will($this->returnValue($expressionResult));
        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue('Asia/Tbilisi'));
        $localeSettings->expects($this->any())
            ->method('getFirstQuarterMonth')
            ->will($this->returnValue(2));
        $localeSettings->expects($this->any())
            ->method('getFirstQuarterDay')
            ->will($this->returnValue(15));

        $this->utility = new DateFilterUtility($localeSettings, $compiler);
    }

    protected function tearDown()
    {
        unset($this->utility);
    }

    /**
     * @dataProvider parseDataProvider
     *
     * @param mixed  $data
     * @param string $fieldName
     * @param mixed  $expectedResults
     */
    public function testParse($data, $fieldName, $expectedResults)
    {
        $this->assertEquals($expectedResults, $this->utility->parseData($fieldName, $data));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function parseDataProvider()
    {
        return [
            'invalid data, not array'                          => [
                null,
                'field',
                false
            ],
            'invalid data, no value key'                       => [
                [],
                'field',
                false
            ],
            'invalid data, no one field given'                 => [
                ['value' => []],
                'field',
                false
            ],
            'valid date given'                                 => [
                ['value' => ['start' => '2001-01-01', 'start_original' => '', 'end_original' => '']],
                'field',
                [
                    'date_start' => '2001-01-01',
                    'date_end'   => null,
                    'date_start_original' => '',
                    'date_end_original' => '',
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_VALUE,
                    'field'      => 'field',
                ]
            ],
            'valid data given, more then given part day'       => [
                [
                    'value' => ['start' => 1, 'end' => 20, 'start_original' => 1, 'end_original' => 3],
                    'type'  => DateRangeFilterType::TYPE_MORE_THAN,
                    'part'  => DateModifierInterface::PART_DAY,
                ],
                'field',
                [
                    'date_start' => 1,
                    'date_end'   => null,
                    'date_start_original' => 1,
                    'date_end_original' => null,
                    'type'       => DateRangeFilterType::TYPE_MORE_THAN,
                    'part'       => DateModifierInterface::PART_DAY,
                    'field'      => "DAY(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, less then given part month'     => [
                [
                    'value' => ['start' => 1, 'end' => 3, 'start_original' => 5, 'end_original' => 6],
                    'type'  => DateRangeFilterType::TYPE_LESS_THAN,
                    'part'  => DateModifierInterface::PART_MONTH,
                ],
                'field',
                [
                    'date_start' => null,
                    'date_end'   => 3,
                    'date_start_original' => null,
                    'date_end_original' => 6,
                    'type'       => DateRangeFilterType::TYPE_LESS_THAN,
                    'part'       => DateModifierInterface::PART_MONTH,
                    'field'      => "MONTH(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part year'        => [
                [
                    'value' => ['start' => 2001, 'end' => 2005, 'start_original' => 2005, 'end_original' => 2012],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_YEAR,
                ],
                'field',
                [
                    'date_start' => 2001,
                    'date_end'   => 2005,
                    'date_start_original' => 2005,
                    'date_end_original' => 2012,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_YEAR,
                    'field'      => "YEAR(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part week'        => [
                [
                    'value' => ['start' => 2, 'end' => 5, 'start_original' => 3, 'end_original' => 7],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_WEEK,
                ],
                'field',
                [
                    'date_start' => 2,
                    'date_end'   => 5,
                    'date_start_original' => 3,
                    'date_end_original' => 7,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_WEEK,
                    'field'      => "WEEK(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part day of week' => [
                [
                    'value' => ['start' => 2, 'end' => 5, 'start_original' => 5, 'end_original' => 8],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_DOW,
                ],
                'field',
                [
                    'date_start' => 2,
                    'date_end'   => 5,
                    'date_start_original' => 5,
                    'date_end_original' => 8,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_DOW,
                    'field'      => "DAYOFWEEK(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part day of year' => [
                [
                    'value' => ['start' => 320, 'end' => 365, 'start_original' => 340, 'end_original' => 350],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_DOY,
                ],
                'field',
                [
                    'date_start' => 320,
                    'date_end'   => 365,
                    'date_start_original' => 340,
                    'date_end_original' => 350,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_DOY,
                    'field'      => "DAYOFYEAR(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part quarter'     => [
                [
                    'value' => ['start' => 1, 'end' => 2, 'start_original' => 2, 'end_original' => 3],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_QUARTER,
                ],
                'field',
                [
                    'date_start' => 1,
                    'date_end'   => 2,
                    'date_start_original' => 2,
                    'date_end_original' => 3,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_QUARTER,
                    'field'      =>
                        "QUARTER(DATE_SUB(DATE_SUB(CONVERT_TZ(field, '+00:00', '+04:00'), 1, 'month'), 14, 'day'))",
                ]
            ],
            'valid data given, equal then given part month'     => [
                [
                    'value' => ['start' => 1, 'end' => 3, 'start_original' => 5, 'end_original' => 6],
                    'type'  => DateRangeFilterType::TYPE_EQUAL,
                    'part'  => DateModifierInterface::PART_MONTH,
                ],
                'field',
                [
                    'date_start' => 1,
                    'date_end'   => 3,
                    'date_start_original' => 5,
                    'date_end_original' => 6,
                    'type'       => DateRangeFilterType::TYPE_EQUAL,
                    'part'       => DateModifierInterface::PART_MONTH,
                    'field'      => "MONTH(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, equal then given part month with current day without year var'     => [
                [
                    'value' => [
                        'start' => new \DateTime('2010-01-02'),
                        'end' => 3,
                        'start_original' => '{{'.DateModifierInterface::VAR_THIS_DAY_W_Y.'}}',
                        'end_original' => 6
                    ],
                    'type'  => DateRangeFilterType::TYPE_EQUAL,
                    'part'  => DateModifierInterface::PART_VALUE,
                ],
                'field',
                [
                    'date_start' => '0102',
                    'date_end'   => 3,
                    'date_start_original' => '{{'.DateModifierInterface::VAR_THIS_DAY_W_Y.'}}',
                    'date_end_original' => 6,
                    'type'       => DateRangeFilterType::TYPE_EQUAL,
                    'part'       => DateModifierInterface::PART_VALUE,
                    'field'      =>
                        "MONTH(CONVERT_TZ(field, '+00:00', '+04:00')) * 100 + ".
                        "DAY(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
        ];
    }
}
