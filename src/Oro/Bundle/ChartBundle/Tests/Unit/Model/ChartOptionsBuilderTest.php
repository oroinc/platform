<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ChartOptionsBuilder;

class ChartOptionsBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChartOptionsBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new ChartOptionsBuilder();
    }

    /**
     * @param array $chartOptions
     * @param array $gridConfig
     * @param array $expected
     *
     * @dataProvider dataProvider
     */
    public function testBuildOptions(array $chartOptions, array $gridConfig, array $expected)
    {
        $result = $this->builder->buildOptions($chartOptions, $gridConfig);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProvider()
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
