<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberRangeFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;

class NumberRangeFilterTest extends NumberFilterTest
{
    /**
     * @var NumberRangeFilter
     */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /* @var $formFactory FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        /* @var $filterUtility FilterUtility|\PHPUnit_Framework_MockObject_MockObject */
        $filterUtility = $this->getMock('Oro\Bundle\FilterBundle\Filter\FilterUtility');

        $this->filter = new NumberRangeFilter($formFactory, $filterUtility);
        $this->filter->init($this->filterName, [
            FilterUtility::DATA_NAME_KEY => $this->dataName,
        ]);
    }

    /**
     * @dataProvider parseDataProvider
     *
     * @param mixed  $inputData
     * @param mixed  $expectedData
     */
    public function testParseData($inputData, $expectedData)
    {
        $this->assertEquals($expectedData, $this->filter->parseData($inputData));
    }

    /**
     * @dataProvider applyRangeProvider
     *
     * @param array $inputData
     * @param array $expectedData
     */
    public function testApplyRange(array $inputData, array $expectedData)
    {
        $ds = $this->prepareDatasource();

        $this->filter->apply($ds, $inputData['data']);

        $where = $this->parseQueryCondition($ds);

        $this->assertEquals($expectedData['where'], $where);
    }

    /**
     * @return array
     */
    public function applyRangeProvider()
    {
        return [
            'BETWEEN x AND y' => [
                'input' => [
                    'data' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => 1,
                        'value_end' => 2
                    ],
                ],
                'expected' => [
                    'where' => 'field-name >= 1 AND field-name <= 2',
                ],
            ],
            'BETWEEN x AND NULL' => [
                'input' => [
                    'data' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => 3,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name >= 3',
                ],
            ],
            'BETWEEN NULL AND y' => [
                'input' => [
                    'data' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value_end' => 4,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name <= 4',
                ],
            ],
            'NOT BETWEEN x AND y' => [
                'input' => [
                    'data' => [
                        'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                        'value' => 5,
                        'value_end' => 6,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name < 5 OR field-name > 6',
                ],
            ],
            'NOT BETWEEN x AND NULL' => [
                'input' => [
                    'data' => [
                        'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                        'value' => 7,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name < 7',
                ],
            ],
            'NOT BETWEEN NULL AND y' => [
                'input' => [
                    'data' => [
                        'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                        'value_end' => 8,
                    ],
                ],
                'expected' => [
                    'where' => 'field-name > 8',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function parseDataProvider()
    {
        return [
            'invalid data, no value' => [
                [],
                false
            ],
            'invalid data, null range start and end' => [
                ['value' => null, 'value_end' => null],
                false
            ],
            'valid data, null type' => [
                ['value' => 1, 'value_end' => 2],
                ['value' => 1, 'value_end' => 2, 'type' => null],
            ],
            'valid data, type is TYPE_EMPTY' => [
                ['value' => null, 'value_end' => null, 'type' => FilterUtility::TYPE_EMPTY],
                ['value' => null, 'value_end' => null, 'type' => FilterUtility::TYPE_EMPTY],
            ],
            'valid data, type is TYPE_NOT_EMPTY' => [
                ['value' => null, 'value_end' => null, 'type' => FilterUtility::TYPE_NOT_EMPTY],
                ['value' => null, 'value_end' => null, 'type' => FilterUtility::TYPE_NOT_EMPTY],
            ],
            'valid data, empty start range' => [
                ['value_end' => 2, 'type' => NumberRangeFilterType::TYPE_BETWEEN],
                ['value' => null, 'value_end' => 2, 'type' => NumberRangeFilterType::TYPE_BETWEEN],
            ],
            'valid data, empty end range' => [
                ['value' => 1, 'value_end' => null, 'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN],
                ['value' => 1, 'value_end' => null, 'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN],
            ],
        ];
    }
}
