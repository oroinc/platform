<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Grid\DefaultColumnFilteringOptionsGuesser;
use PHPUnit\Framework\TestCase;

class DefaultColumnFilteringOptionsGuesserTest extends TestCase
{
    private DefaultColumnFilteringOptionsGuesser $guesser;

    #[\Override]
    protected function setUp(): void
    {
        $this->guesser = new DefaultColumnFilteringOptionsGuesser();
    }

    /**
     * @dataProvider guessFilterProvider
     */
    public function testGuessFilter($type, $expected): void
    {
        $guess = $this->guesser->guessFilter('TestClass', 'testProp', $type);
        $this->assertEquals($expected, $guess->getOptions());
        $this->assertEquals(ColumnGuess::LOW_CONFIDENCE, $guess->getConfidence());
    }

    public function guessFilterProvider(): array
    {
        return [
            [
                'integer',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_INTEGER,
                        'source_type' => 'integer'
                    ]
                ]
            ],
            [
                'smallint',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_SMALLINT,
                        'source_type' => 'smallint'
                    ]
                ]
            ],
            [
                'bigint',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_BIGINT,
                        'source_type' => 'bigint'
                    ]
                ]
            ],
            [
                'decimal',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_DECIMAL,
                        'source_type' => 'decimal'
                    ]
                ]
            ],
            [
                'float',
                [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_DECIMAL,
                        'source_type' => 'float'
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
                    'type' => 'number-range',
                    'source_type' => 'money'
                ]
            ],
            [
                'percent',
                [
                    'type' => 'percent',
                    'source_type' => 'percent'
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
