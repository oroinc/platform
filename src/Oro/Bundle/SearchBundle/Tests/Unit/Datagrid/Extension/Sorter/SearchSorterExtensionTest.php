<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Datagrid\Extension\Sorter\SearchSorterExtension;

class SearchSorterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchSorterExtension
     */
    protected $sorter;

    public function setUp()
    {
        $this->sorter = new SearchSorterExtension();
    }

    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $this->sorter->setParameters(new ParameterBag($input));
        $this->assertEquals($expected, $this->sorter->getParameters()->all());
    }

    /**
     * @return array
     */
    public function setParametersDataProvider()
    {
        return [
            'empty' => [
                'input' => [],
                'expected' => [],
            ],
            'regular' => [
                'input' => [
                    SearchSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => SearchSorterExtension::DIRECTION_ASC,
                        'lastName' => SearchSorterExtension::DIRECTION_DESC,
                    ]
                ],
                'expected' => [
                    SearchSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => SearchSorterExtension::DIRECTION_ASC,
                        'lastName' => SearchSorterExtension::DIRECTION_DESC,
                    ]
                ]
            ],
            'minified' => [
                'input' => [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        SearchSorterExtension::MINIFIED_SORTERS_PARAM => [
                            'firstName' => '-1',
                            'lastName' => '1',
                        ]
                    ]
                ],
                'expected' => [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        SearchSorterExtension::MINIFIED_SORTERS_PARAM => [
                            'firstName' => '-1',
                            'lastName' => '1',
                        ]
                    ],
                    SearchSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => SearchSorterExtension::DIRECTION_ASC,
                        'lastName' => SearchSorterExtension::DIRECTION_DESC,
                    ]
                ]
            ],
        ];
    }

    /**
     * @param array $sorters
     * @param array $columns
     * @param array $expectedData
     *
     * @dataProvider visitMetadataDataProvider
     */
    public function testVisitMetadata(array $sorters, array $columns, array $expectedData)
    {
        $config = DatagridConfiguration::create(['sorters' => $sorters]);

        $data = MetadataObject::create(['columns' => $columns]);
        $this->sorter->setParameters(new ParameterBag());
        $this->sorter->visitMetadata($config, $data);
        $this->assertEquals($expectedData, $data->toArray());
    }

    /**
     * @return array
     */
    public function visitMetadataDataProvider()
    {
        return [
            'sortable' => [
                'sorters' => [
                    'columns' => [
                        'name' => [],
                    ],
                ],
                'columns' => [
                    ['name' => 'name'],
                    ['name' => 'createdAt'],
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options' => [
                        'multipleSorting' => false,
                        'toolbarOptions' => [
                            'addSorting' => false,
                        ],
                    ],
                    'initialState' => ['sorters' => ['name' => 'ASC',]],
                    'state' => ['sorters' => ['name' => 'ASC',]],
                ]
            ],
            'multiple' => [
                'sorters' => [
                    'columns' => [
                        'name' => [],
                    ],
                    'multiple_sorting' => true,
                ],
                'columns' => [
                    ['name' => 'name'],
                    ['name' => 'createdAt'],
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options' => [
                        'multipleSorting' => true,
                        'toolbarOptions' => [
                            'addSorting' => false,
                        ],
                    ],
                    'initialState' => ['sorters' => ['name' => 'ASC',]],
                    'state' => ['sorters' => ['name' => 'ASC',]],
                ]
            ],
            'toolbar' => [
                'sorters' => [
                    'columns' => [
                        'name' => ['type' => 'string'],
                        'age' => [],
                    ],
                    'toolbar_sorting' => true,
                ],
                'columns' => [
                    ['name' => 'name'],
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                            'sortingType' => 'string',
                        ],
                        [
                            'name' => 'age',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options' => [
                        'multipleSorting' => false,
                        'toolbarOptions' => [
                            'addSorting' => true,
                        ],
                    ],
                    'initialState' => ['sorters' => ['name' => 'ASC',]],
                    'state' => ['sorters' => ['name' => 'ASC',]],
                ]
            ]
        ];
    }

    /**
     * @dataProvider visitMetadataUnknownColumnDataProvider
     * @param array $sorters
     * @param array $columns
     * @param string $expectedMessage
     */
    public function testVisitMetadataUnknownColumn(array $sorters, array $columns, $expectedMessage)
    {
        $this->setExpectedException('\Oro\Bundle\DataGridBundle\Exception\LogicException', $expectedMessage);
        $config = DatagridConfiguration::create(['sorters' => $sorters]);

        $data = MetadataObject::create(['columns' => $columns]);
        $this->sorter->setParameters(new ParameterBag());
        $this->sorter->visitMetadata($config, $data);
    }

    /**
     * @return array
     */
    public function visitMetadataUnknownColumnDataProvider()
    {
        return [
            'unknown column' => [
                'sorters' => [
                    'columns' => [
                        'unknown' => [],
                        'age' => [],
                    ],
                ],
                'columns' => [
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedMessage' => 'Could not found column(s) "unknown" for sorting',
            ],
            'unknown single column' => [
                'sorters' => [
                    'columns' => [
                        'unknown' => [],
                    ],
                ],
                'columns' => [
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedMessage' => 'Could not found column(s) "unknown" for sorting',
            ],
        ];
    }

    /**
     * @param $configDataType A valid configuration datatype (eg. string or integer)
     *
     * @dataProvider visitDatasourceWithValidTypeProvider
     */
    public function testVisitDatasourceWithValidType($configDataType)
    {
        $config = DatagridConfiguration::create([
            'sorters' => [
                'columns' => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type' => $configDataType,
                    ]
                ]
            ]
        ]);

        $mockQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->getMock();

        $mockDatasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockDatasource
            ->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($mockQuery);

        $mockParameterBag = $this->getMockBuilder(ParameterBag::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->sorter->setParameters($mockParameterBag);

        $this->sorter->visitDatasource($config, $mockDatasource);
    }

    /**
     * @return array
     */
    public function visitDatasourceWithValidTypeProvider()
    {
        return [
            'string' => [
                'configDataType' =>'string',
            ],
            'integer' => [
                'configDataType' => 'integer',
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\SearchBundle\Exception\InvalidConfigurationException
     */
    public function testVisitDatasourceWithInvalidType()
    {
        $config = DatagridConfiguration::create([
            'sorters' => [
                'columns' => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type' => 'this_will_not_be_a_valid_type',
                    ]
                ]
            ]
        ]);

        $mockDatasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockParameterBag = $this->getMockBuilder(ParameterBag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sorter->setParameters($mockParameterBag);
        $this->sorter->visitDatasource($config, $mockDatasource);
    }
}
