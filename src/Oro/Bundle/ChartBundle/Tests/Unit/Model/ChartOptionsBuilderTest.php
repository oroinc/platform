<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ChartOptionsBuilder;

class ChartOptionsBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChartOptionsBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new ChartOptionsBuilder();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testBuildOptions(array $chartOptions, array $gridConfig, array $expected)
    {
        $result = $this->builder->buildOptions($chartOptions, $gridConfig);

        $this->assertEquals($expected, $result);
    }

    public function dataProvider(): array
    {
        return [
            'empty'      => [
                'chartOptions' => [],
                'gridConfig'   => [],
                'expected'     => [],
            ],
            'no_aliases' => [
                'chartOptions' => [
                    'data_schema' => [
                        'param1' => 'property1'
                    ]
                ],
                'gridConfig'   => [],
                'expected'     => [
                    'data_schema' => [
                        'param1' => 'property1'
                    ]
                ],
            ],
            'build'      => [
                'chartOptions' => [
                    'data_schema' => [
                        'param1' => 'property1'
                    ]
                ],
                'gridConfig'   => [
                    'source' => [
                        'query_config' => [
                            'column_aliases' => [
                                'property1' => 'c1'
                            ]
                        ]
                    ]
                ],
                'expected'     => [
                    'data_schema' => [
                        'param1' => 'c1'
                    ]
                ],
            ],
        ];
    }
}
