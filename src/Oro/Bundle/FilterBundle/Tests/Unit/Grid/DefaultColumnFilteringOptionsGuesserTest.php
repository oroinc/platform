<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Grid\DefaultColumnFilteringOptionsGuesser;

class DefaultColumnFilteringOptionsGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var DefaultColumnFilteringOptionsGuesser */
    protected $guesser;

    protected function setUp()
    {
        $this->guesser = new DefaultColumnFilteringOptionsGuesser();
    }

    /**
     * @dataProvider guessFilterProvider
     */
    public function testGuessFilter($type, $expected)
    {
        $guess = $this->guesser->guessFilter('TestClass', 'testProp', $type);
        $this->assertEquals($expected, $guess->getOptions());
        $this->assertEquals(ColumnGuess::LOW_CONFIDENCE, $guess->getConfidence());
    }

    public function guessFilterProvider()
    {
        return [
            [
                'integer',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_INTEGER
                    ]
                ]
            ],
            [
                'smallint',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_INTEGER
                    ]
                ]
            ],
            [
                'bigint',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_INTEGER
                    ]
                ]
            ],
            [
                'decimal',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_DECIMAL
                    ]
                ]
            ],
            [
                'float',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_DECIMAL
                    ]
                ]
            ],
            [
                'boolean',
                [
                    'type' => 'boolean'
                ]
            ],
            [
                'date',
                [
                    'type' => 'date'
                ]
            ],
            [
                'datetime',
                [
                    'type' => 'datetime'
                ]
            ],
            [
                'money',
                [
                    'type' => 'number-range'
                ]
            ],
            [
                'percent',
                [
                    'type' => 'percent'
                ]
            ],
            [
                'string',
                [
                    'type' => 'string'
                ]
            ],
            [
                'other',
                [
                    'type' => 'string'
                ]
            ],
        ];
    }
}
