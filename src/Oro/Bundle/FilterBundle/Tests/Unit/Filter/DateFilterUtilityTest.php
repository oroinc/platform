<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\DateFilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

class DateFilterUtilityTest extends \PHPUnit_Framework_TestCase
{
    /** @var DateFilterUtility */
    protected $utility;

    public function setUp()
    {
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(array('getTimezone'))
            ->getMock();
        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue('Europe/Moscow'));

        $this->utility = new DateFilterUtility($localeSettings);
    }

    public function tearDown()
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
                ['value' => ['start' => '2001-01-01']],
                'field',
                [
                    'date_start' => '2001-01-01',
                    'date_end'   => null,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_VALUE,
                    'field'      => 'field',
                ]
            ],
            'valid data given, more then given part day'       => [
                [
                    'value' => ['start' => 1, 'end' => 20],
                    'type'  => DateRangeFilterType::TYPE_MORE_THAN,
                    'part'  => DateModifierInterface::PART_DAY,
                ],
                'field',
                [
                    'date_start' => 1,
                    'date_end'   => null,
                    'type'       => DateRangeFilterType::TYPE_MORE_THAN,
                    'part'       => DateModifierInterface::PART_DAY,
                    'field'      => "DAY(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, less then given part month'     => [
                [
                    'value' => ['start' => 1, 'end' => 3],
                    'type'  => DateRangeFilterType::TYPE_LESS_THAN,
                    'part'  => DateModifierInterface::PART_MONTH,
                ],
                'field',
                [
                    'date_start' => null,
                    'date_end'   => 3,
                    'type'       => DateRangeFilterType::TYPE_LESS_THAN,
                    'part'       => DateModifierInterface::PART_MONTH,
                    'field'      => "MONTH(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part year'        => [
                [
                    'value' => ['start' => 2001, 'end' => 2005],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_YEAR,
                ],
                'field',
                [
                    'date_start' => 2001,
                    'date_end'   => 2005,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_YEAR,
                    'field'      => "YEAR(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part week'        => [
                [
                    'value' => ['start' => 2, 'end' => 5],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_WEEK,
                ],
                'field',
                [
                    'date_start' => 2,
                    'date_end'   => 5,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_WEEK,
                    'field'      => "WEEK(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part day of week' => [
                [
                    'value' => ['start' => 2, 'end' => 5],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_DOW,
                ],
                'field',
                [
                    'date_start' => 2,
                    'date_end'   => 5,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_DOW,
                    'field'      => "DAYOFWEEK(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part day of year' => [
                [
                    'value' => ['start' => 320, 'end' => 365],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_DOY,
                ],
                'field',
                [
                    'date_start' => 320,
                    'date_end'   => 365,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_DOY,
                    'field'      => "DAYOFYEAR(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ],
            'valid data given, between given part quarter'     => [
                [
                    'value' => ['start' => 1, 'end' => 2],
                    'type'  => DateRangeFilterType::TYPE_BETWEEN,
                    'part'  => DateModifierInterface::PART_QUARTER,
                ],
                'field',
                [
                    'date_start' => 1,
                    'date_end'   => 2,
                    'type'       => DateRangeFilterType::TYPE_BETWEEN,
                    'part'       => DateModifierInterface::PART_QUARTER,
                    'field'      => "QUARTER(CONVERT_TZ(field, '+00:00', '+04:00'))",
                ]
            ]
        ];
    }
}
